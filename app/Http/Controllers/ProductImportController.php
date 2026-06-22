<?php

namespace App\Http\Controllers;

use App\Services\ProductImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductImportController extends Controller
{
    public function create(): View
    {
        return view('products.import.upload');
    }

    public function template(): Response
    {
        $headers = array_values(ProductImportService::FIELDS);
        $examples = [
            [
                'Basmati Rice', '', '', '', '', '', '', '', '',
                'Groceries', 'Rice', 'Basmati Rice', 'Store Brand',
                'RICE-BAS-001', '896400000001', 'Premium basmati rice',
                'KG', 'Bag', '50', '12000', '280', '300', '3', '10', '10', 'Active',
            ],
            [
                'Cotton T-Shirt', 'TSHIRT', 'Black / Small', 'Color', 'Black', 'Size', 'Small', '', '',
                'Clothing', 'Shirts', '', 'Demo Fashion',
                'TSHIRT-BLK-S', '896400000101', 'Cotton crew-neck shirt',
                'Piece', 'Carton', '24', '12000', '750', '900', '1', '0', '5', 'Active',
            ],
            [
                'Cotton T-Shirt', 'TSHIRT', 'Blue / Large', 'Color', 'Blue', 'Size', 'Large', '', '',
                'Clothing', 'Shirts', '', 'Demo Fashion',
                'TSHIRT-BLU-L', '896400000102', 'Cotton crew-neck shirt',
                'Piece', 'Carton', '24', '12000', '800', '950', '1', '6', '5', 'Active',
            ],
        ];

        return response($this->csv([$headers, ...$examples]), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="billstack-product-import-template.csv"',
        ]);
    }

    public function preview(Request $request, ProductImportService $importer): View|RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'rb');
        $headers = fgetcsv($handle) ?: [];
        if (isset($headers[0])) {
            $headers[0] = ltrim((string) $headers[0], "\xEF\xBB\xBF");
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false && count($rows) < 1000) {
            if (collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isNotEmpty()) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        if (! $headers || ! $rows) {
            return back()->withErrors(['csv_file' => 'The CSV must contain a header row and at least one product row.']);
        }

        $token = (string) Str::uuid();
        session(["product_import.{$token}" => compact('headers', 'rows')]);

        return view('products.import.preview', [
            'token' => $token,
            'headers' => $headers,
            'rows' => $rows,
            'fields' => ProductImportService::FIELDS,
            'requiredFields' => ProductImportService::REQUIRED_FIELDS,
            'mapping' => $importer->suggestedMapping($headers),
        ]);
    }

    public function store(Request $request, ProductImportService $importer): View|RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'uuid'],
            'mapping' => ['required', 'array'],
            'mapping.*' => ['nullable', 'integer', 'min:0'],
            'create_missing' => ['nullable', 'boolean'],
        ]);
        $payload = session("product_import.{$data['token']}");
        if (! $payload) {
            return redirect()->route('products.import')->withErrors([
                'csv_file' => 'The import preview expired. Upload the CSV again.',
            ]);
        }

        foreach (ProductImportService::REQUIRED_FIELDS as $field) {
            if (($data['mapping'][$field] ?? '') === '') {
                return back()->withErrors(["mapping.{$field}" => ProductImportService::FIELDS[$field].' must be mapped.']);
            }
        }

        $result = $importer->import(
            $payload['rows'],
            $data['mapping'],
            $request->boolean('create_missing')
        );
        $errorToken = null;
        if ($result['errors']) {
            $errorToken = (string) Str::uuid();
            session(["product_import_errors.{$errorToken}" => $result['errors']]);
        }
        session()->forget("product_import.{$data['token']}");

        return view('products.import.result', compact('result', 'errorToken'));
    }

    public function errors(string $token): Response
    {
        $errors = session("product_import_errors.{$token}");
        abort_unless(is_array($errors), 404);

        return response($this->csv([
            ['Line', 'SKU', 'Product Name', 'Error'],
            ...array_map(fn ($error) => array_values($error), $errors),
        ]), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="billstack-product-import-errors.csv"',
        ]);
    }

    private function csv(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        return $contents ?: '';
    }
}
