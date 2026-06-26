# Zephrant ERP Architecture Diagram

## System Overview

Zephrant ERP is a multi-tenant Laravel-based business management SaaS platform for inventory, billing, customer/supplier management, and operations.

## Tech Stack

- **Backend**: PHP 8.3+, Laravel 13, Laravel Breeze, Spatie Laravel Permission
- **Frontend**: Blade templates, TailwindCSS, Alpine.js, Tabler UI, ApexCharts
- **Build Tools**: Vite, PostCSS
- **PDF Generation**: DomPDF
- **Testing**: PHPUnit (80 tests, 427 assertions)
- **Database**: MySQL/SQLite with tenant-scoped data

## High-Level Architecture

```mermaid
graph TB
    subgraph "Client Layer"
        Browser[Browser - Blade UI]
    end
    
    subgraph "Application Layer"
        Routes[Routes - web.php]
        Middleware[Middleware - Auth, Tenant, Permissions]
        Controllers[Controllers]
        Services[Domain Services]
        Validation[Form Requests]
    end
    
    subgraph "Data Layer"
        Models[Eloquent Models]
        Database[(Tenant-Scoped Database)]
    end
    
    subgraph "Output Layer"
        Views[Blade Views]
        PDF[PDF Generation]
        Reports[Reports]
    end
    
    Browser --> Routes
    Routes --> Middleware
    Middleware --> Controllers
    Controllers --> Validation
    Validation --> Services
    Services --> Models
    Models --> Database
    Services --> Views
    Services --> PDF
    Services --> Reports
    Views --> Browser
```

## Multi-Tenant Isolation Architecture

```mermaid
graph LR
    subgraph "Tenant Isolation Mechanism"
        Auth[Auth User]
        Trait[BelongsToTenant Trait]
        Scope[Global Scope]
        Validation[Tenant-Scoped Validation]
    end
    
    subgraph "Protected Models"
        Product[Product]
        Customer[Customer]
        Supplier[Supplier]
        Invoice[Invoice]
        Purchase[Purchase]
    end
    
    Auth --> Trait
    Trait --> Scope
    Scope --> Product
    Scope --> Customer
    Scope --> Supplier
    Scope --> Invoice
    Scope --> Purchase
    Validation --> Product
    Validation --> Customer
    Validation --> Supplier
    Validation --> Invoice
    Validation --> Purchase
```

### Tenant Isolation Implementation

The `BelongsToTenant` trait enforces data isolation at three levels:

1. **Query Scope**: Global scope automatically filters all queries by `tenant_id`
2. **Auto-Assignment**: Automatically assigns `tenant_id` on record creation
3. **Validation**: Form requests validate that referenced records belong to the same tenant

## Core Business Modules

```mermaid
graph TB
    subgraph "Inventory Module"
        Products[Products]
        Categories[Categories]
        Brands[Brands]
        Units[Units]
        Variants[Product Variants]
        Attributes[Product Attributes]
    end
    
    subgraph "Billing Module"
        Invoices[Invoices]
        InvoiceItems[Invoice Items]
        POS[POS Billing]
        Payments[Customer Payments]
    end
    
    subgraph "Purchase Module"
        Purchases[Purchases]
        PurchaseItems[Purchase Items]
        SupplierPayments[Supplier Payments]
    end
    
    subgraph "Returns Module"
        SalesReturns[Sales Returns]
        PurchaseReturns[Purchase Returns]
    end
    
    subgraph "Ledger Module"
        StockLedger[Stock Ledger]
        CustomerLedger[Customer Account]
        SupplierLedger[Supplier Account]
    end
    
    subgraph "Reports Module"
        SalesReports[Sales Reports]
        StockReports[Stock Reports]
        ProfitLoss[Profit/Loss]
        LowStock[Low Stock Alerts]
    end
    
    Products --> Variants
    Products --> StockLedger
    Invoices --> InvoiceItems
    Invoices --> CustomerLedger
    Invoices --> StockLedger
    Purchases --> PurchaseItems
    Purchases --> SupplierLedger
    Purchases --> StockLedger
    SalesReturns --> StockLedger
    SalesReturns --> CustomerLedger
    PurchaseReturns --> StockLedger
    PurchaseReturns --> SupplierLedger
```

## Sales Transaction Flow

```mermaid
sequenceDiagram
    participant User
    participant Controller
    participant Validation
    participant StockLedger
    participant ProductCatalog
    participant Database
    
    User->>Controller: Create Invoice/POS Sale
    Controller->>Validation: Validate Request
    Validation->>Validation: Check tenant ownership
    Validation->>Validation: Check stock availability
    Validation-->>Controller: Validated Data
    
    Controller->>ProductCatalog: Resolve Product Variants
    ProductCatalog-->>Controller: Variant Details
    
    Controller->>Database: Begin Transaction
    Controller->>Database: Create Invoice
    Controller->>Database: Create Invoice Items
    Controller->>StockLedger: Record Stock Movement (OUT)
    StockLedger->>Database: Create StockMovement Record
    Controller->>Database: Update Product Stock
    Controller->>Database: Create Customer Receivable
    Controller->>Database: Commit Transaction
    
    Controller-->>User: Invoice Created
```

## Purchase Transaction Flow

```mermaid
sequenceDiagram
    participant User
    participant Controller
    participant Validation
    participant StockLedger
    participant Database
    
    User->>Controller: Create Purchase
    Controller->>Validation: Validate Request
    Validation->>Validation: Check tenant ownership
    Validation->>Validation: Validate supplier
    Validation-->>Controller: Validated Data
    
    Controller->>Database: Begin Transaction
    Controller->>Database: Create Purchase
    Controller->>Database: Create Purchase Items
    Controller->>StockLedger: Record Stock Movement (IN)
    StockLedger->>Database: Create StockMovement Record
    Controller->>Database: Update Product Stock
    Controller->>Database: Create Supplier Payable
    Controller->>Database: Commit Transaction
    
    Controller-->>User: Purchase Created
```

## Payment Allocation Flow

```mermaid
sequenceDiagram
    participant User
    participant Controller
    participant PaymentService
    participant Database
    
    User->>Controller: Record Payment
    Controller->>PaymentService: Allocate Payment
    PaymentService->>Database: Begin Transaction
    PaymentService->>Database: Lock Customer Invoices
    PaymentService->>Database: Get Outstanding Invoices
    PaymentService->>Database: Apply to Oldest Invoice First
    
    loop For Each Invoice
        PaymentService->>Database: Create Payment Record
        PaymentService->>Database: Update Invoice Paid Amount
        PaymentService->>Database: Update Invoice Status
    end
    
    PaymentService->>Database: Commit Transaction
    PaymentService-->>Controller: Allocation Complete
    Controller-->>User: Payment Recorded
```

## Stock Ledger System

```mermaid
graph TD
    subgraph "Stock Movement Sources"
        Sale[Invoice/POS Sale]
        Purchase[Purchase Order]
        SalesReturn[Sales Return]
        PurchaseReturn[Purchase Return]
        Cancellation[Document Cancellation]
        Adjustment[Stock Adjustment]
    end
    
    subgraph "Stock Ledger Service"
        Record[Record Movement]
        Direction[Direction Logic]
        Audit[Audit Trail]
    end
    
    subgraph "Stock Movement Records"
        Movement[StockMovement Table]
        Fields[Type, Direction, Quantity, Unit Cost, Unit Price, Stock After, Source, Reference, Date]
    end
    
    subgraph "Product Stock"
        Product[Product Stock Quantity]
        Variant[Variant Stock Quantity]
    end
    
    Sale --> Record
    Purchase --> Record
    SalesReturn --> Record
    PurchaseReturn --> Record
    Cancellation --> Record
    Adjustment --> Record
    
    Record --> Direction
    Record --> Movement
    Movement --> Fields
    Movement --> Product
    Movement --> Variant
    Direction --> Movement
```

### Stock Movement Types

- **sale**: Stock OUT (invoice/POS sale)
- **purchase**: Stock IN (supplier purchase)
- **sales_return**: Stock IN (customer return)
- **purchase_return**: Stock OUT (supplier return)
- **purchase_cancel**: Stock OUT (purchase cancellation)
- **stock_adjustment_out**: Stock OUT (manual adjustment)
- **stock_adjustment_in**: Stock IN (manual adjustment)

## Account Ledger System

```mermaid
graph LR
    subgraph "Customer Account"
        Invoices[Invoices]
        Payments[Customer Payments]
        Returns[Sales Returns]
        Balance[Customer Balance]
    end
    
    subgraph "Supplier Account"
        Purchases[Purchases]
        SupplierPayments[Supplier Payments]
        PurchaseReturns[Purchase Returns]
        SupplierBalance[Supplier Balance]
    end
    
    subgraph "Balance Calculation"
        CustomerCalc[Invoice Total - Payments - Return Credits]
        SupplierCalc[Purchase Total - Payments - Return Credits]
    end
    
    Invoices --> CustomerCalc
    Payments --> CustomerCalc
    Returns --> CustomerCalc
    CustomerCalc --> Balance
    
    Purchases --> SupplierCalc
    SupplierPayments --> SupplierCalc
    PurchaseReturns --> SupplierCalc
    SupplierCalc --> SupplierBalance
```

## Controller-Service-Model Pattern

```mermaid
graph TB
    subgraph "Controllers (HTTP Layer)"
        InvoiceController[InvoiceController]
        PurchaseController[PurchaseController]
        ProductController[ProductController]
        CustomerController[CustomerController]
        SupplierController[SupplierController]
    end
    
    subgraph "Services (Domain Logic)"
        StockLedgerService[StockLedgerService]
        ProductCatalogService[ProductCatalogService]
        CustomerPaymentService[CustomerPaymentAllocationService]
        SupplierAccountService[SupplierAccountService]
        PurchaseService[PurchaseService]
        AlertService[AlertService]
        AnalyticsService[AnalyticsService]
    end
    
    subgraph "Models (Data Layer)"
        Invoice[Invoice]
        Purchase[Purchase]
        Product[Product]
        Customer[Customer]
        Supplier[Supplier]
        StockMovement[StockMovement]
    end
    
    InvoiceController --> StockLedgerService
    InvoiceController --> ProductCatalogService
    InvoiceController --> CustomerPaymentService
    PurchaseController --> StockLedgerService
    PurchaseController --> SupplierAccountService
    PurchaseController --> PurchaseService
    ProductController --> ProductCatalogService
    CustomerController --> CustomerPaymentService
    SupplierController --> SupplierAccountService
    
    StockLedgerService --> StockMovement
    ProductCatalogService --> Product
    CustomerPaymentService --> Invoice
    CustomerPaymentService --> Customer
    SupplierAccountService --> Purchase
    SupplierAccountService --> Supplier
    PurchaseService --> Purchase
```

## Subscription & Platform Architecture

```mermaid
graph TB
    subgraph "Platform Admin"
        PlatformDashboard[Platform Dashboard]
        TenantManagement[Tenant Management]
        PlanManagement[Plan Management]
        PlatformBilling[Platform Billing]
    end
    
    subgraph "Tenant Subscription"
        Subscription[TenantSubscription]
        Plan[SubscriptionPlan]
        Features[Plan Features]
        Modules[Business Modules]
    end
    
    subgraph "Tenant Application"
        TenantDashboard[Tenant Dashboard]
        BusinessModules[Business Modules Access]
        UsageLimits[Usage Limits]
    end
    
    PlatformDashboard --> TenantManagement
    PlatformDashboard --> PlanManagement
    PlatformDashboard --> PlatformBilling
    
    TenantManagement --> Subscription
    PlanManagement --> Plan
    Plan --> Features
    Plan --> Modules
    
    Subscription --> TenantDashboard
    Modules --> BusinessModules
    Subscription --> UsageLimits
```

## Middleware Stack

```mermaid
graph LR
    Request[HTTP Request] --> Auth[Auth Middleware]
    Auth --> Verified[Verified Middleware]
    Verified --> Role[Role Middleware]
    Role --> Subscription[Active Subscription Middleware]
    Subscription --> Module[Module Access Middleware]
    Module --> Permission[Permission Middleware]
    Permission --> Controller[Controller]
    
    Auth --> TenantCheck[Tenant Check]
    TenantCheck --> Controller
```

## Database Schema Overview

```mermaid
erDiagram
    TENANT ||--o{ USER : has
    TENANT ||--o{ PRODUCT : owns
    TENANT ||--o{ CUSTOMER : owns
    TENANT ||--o{ SUPPLIER : owns
    TENANT ||--o{ INVOICE : owns
    TENANT ||--o{ PURCHASE : owns
    TENANT ||--o{ STOCK_MOVEMENT : owns
    TENANT ||--o{ TENANT_SUBSCRIPTION : has
    
    PRODUCT ||--o{ PRODUCT_VARIANT : has
    PRODUCT ||--o{ INVOICE_ITEM : appears_in
    PRODUCT ||--o{ PURCHASE_ITEM : appears_in
    PRODUCT ||--o{ STOCK_MOVEMENT : tracks
    
    PRODUCT_VARIANT ||--o{ INVOICE_ITEM : appears_in
    PRODUCT_VARIANT ||--o{ PURCHASE_ITEM : appears_in
    
    CUSTOMER ||--o{ INVOICE : has
    CUSTOMER ||--o{ CUSTOMER_PAYMENT : makes
    
    INVOICE ||--o{ INVOICE_ITEM : contains
    INVOICE ||--o{ CUSTOMER_PAYMENT : receives
    INVOICE ||--o{ SALES_RETURN : may_have
    
    SUPPLIER ||--o{ PURCHASE : has
    SUPPLIER ||--o{ SUPPLIER_PAYMENT : makes
    
    PURCHASE ||--o{ PURCHASE_ITEM : contains
    PURCHASE ||--o{ SUPPLIER_PAYMENT : receives
    PURCHASE ||--o{ PURCHASE_RETURN : may_have
    
    STOCK_MOVEMENT }o--|| PRODUCT : tracks
    STOCK_MOVEMENT }o--|| PRODUCT_VARIANT : tracks
```

## Key Services & Responsibilities

| Service | Responsibility |
|---------|---------------|
| **StockLedgerService** | Records all stock movements with audit trail |
| **ProductCatalogService** | Resolves product variants, manages catalog logic |
| **CustomerPaymentAllocationService** | Allocates payments to invoices (FIFO) |
| **SupplierAccountService** | Manages supplier ledger and balance calculations |
| **PurchaseService** | Handles purchase workflow and stock updates |
| **AlertService** | Generates low-stock and operational alerts |
| **AnalyticsService** | Calculates dashboard metrics and KPIs |
| **TenantSubscriptionService** | Manages subscription lifecycle |
| **PlatformBillingService** | Handles platform-wide billing |
| **TenantUsageLimitService** | Enforces per-tenant usage limits |

## Transaction Safety

All critical business operations run within database transactions:

- Invoice creation (stock deduction, receivable creation)
- Purchase creation (stock addition, payable creation)
- Payment allocation (multiple invoice updates)
- Returns processing (stock restoration, account credits)
- Cancellations (stock reversal, status updates)

## Cancellation Rules

Financial records are preserved after activity:

- Only unpaid invoices/purchases can be cancelled
- Cancellation reverses inventory once
- Transactions with payments/returns remain as historical records
- Stock ledger records the reversal
- Account balances reflect the cancellation

## Testing Strategy

```mermaid
graph TB
    subgraph "Test Coverage"
        TenantIsolation[Tenant Isolation Tests]
        Accounting[Accounting Tests]
        StockLedger[Stock Ledger Tests]
        PaymentAllocation[Payment Allocation Tests]
        ReturnWorkflows[Return Workflow Tests]
        Cancellation[Cancellation Tests]
        Validation[Validation Tests]
        Reports[Report Tests]
    end
    
    subgraph "Test Infrastructure"
        SQLite[In-Memory SQLite]
        PHPUnit[PHPUnit]
        Seeders[Demo Seeders]
        Factories[Model Factories]
    end
    
    TenantIsolation --> SQLite
    Accounting --> SQLite
    StockLedger --> SQLite
    PaymentAllocation --> SQLite
    ReturnWorkflows --> SQLite
    Cancellation --> SQLite
    Validation --> SQLite
    Reports --> SQLite
    
    SQLite --> PHPUnit
    Seeders --> PHPUnit
    Factories --> PHPUnit
```

## Feature Flags & Module System

```mermaid
graph LR
    subgraph "Business Modules"
        Inventory[INVENTORY]
        Billing[BILLING]
        CustomerLedger[CUSTOMER_LEDGER]
        SupplierLedger[SUPPLIER_LEDGER]
        Purchases[PURCHASES]
        Returns[RETURNS]
        Reports[REPORTS]
        TeamManagement[TEAM_MANAGEMENT]
        ProductVariants[PRODUCT_VARIANTS]
        LowStockAlerts[LOW_STOCK_ALERTS]
        BatchExpiry[BATCH_EXPIRY]
    end
    
    subgraph "Feature Flags"
        ProBarcode[pro.barcode]
    end
    
    subgraph "Access Control"
        ModuleMiddleware[Module Middleware]
        FeatureMiddleware[Feature Middleware]
        PermissionMiddleware[Permission Middleware]
    end
    
    Inventory --> ModuleMiddleware
    Billing --> ModuleMiddleware
    CustomerLedger --> ModuleMiddleware
    SupplierLedger --> ModuleMiddleware
    Purchases --> ModuleMiddleware
    Returns --> ModuleMiddleware
    Reports --> ModuleMiddleware
    TeamManagement --> ModuleMiddleware
    ProductVariants --> ModuleMiddleware
    LowStockAlerts --> ModuleMiddleware
    BatchExpiry --> ModuleMiddleware
    
    ProBarcode --> FeatureMiddleware
    ModuleMiddleware --> PermissionMiddleware
    FeatureMiddleware --> PermissionMiddleware
```

## Deployment Architecture

```mermaid
graph TB
    subgraph "Development"
        Local[Local Environment]
        SQLite[SQLite Database]
        DevServer[php artisan serve]
        ViteDev[Vite Dev Server]
    end
    
    subgraph "CI/CD"
        GitHub[GitHub Actions]
        Tests[PHPUnit Tests]
        Build[Vite Production Build]
        Audit[Dependency Audit]
    end
    
    subgraph "Production"
        WebServer[Web Server]
        Database[MySQL/PostgreSQL]
        Queue[Queue Worker]
        Cache[Redis Cache]
        Storage[Storage Link]
    end
    
    Local --> DevServer
    Local --> ViteDev
    
    GitHub --> Tests
    GitHub --> Build
    GitHub --> Audit
    
    Tests --> WebServer
    Build --> WebServer
    Audit --> WebServer
    
    WebServer --> Database
    WebServer --> Queue
    WebServer --> Cache
    WebServer --> Storage
```

## Security Architecture

```mermaid
graph LR
    subgraph "Authentication"
        LaravelBreeze[Laravel Breeze]
        Session[Session Based Auth]
        Verification[Email Verification]
    end
    
    subgraph "Authorization"
        Roles[Roles: Owner, Admin, Staff]
        Permissions[Spatie Permissions]
        ModuleAccess[Module-Based Access]
    end
    
    subgraph "Data Security"
        TenantIsolation[Tenant Isolation]
        Validation[Tenant-Scoped Validation]
        SoftDeletes[Soft Deletes]
        TransactionSafety[Transaction Safety]
    end
    
    LaravelBreeze --> Session
    Session --> Verification
    Verification --> Roles
    
    Roles --> Permissions
    Permissions --> ModuleAccess
    
    ModuleAccess --> TenantIsolation
    TenantIsolation --> Validation
    Validation --> SoftDeletes
    SoftDeletes --> TransactionSafety
```

## Summary

Zephrant ERP is a well-architected multi-tenant SaaS platform with:

- **Clean separation of concerns**: Controllers handle HTTP, Services contain business logic, Models manage data
- **Strong tenant isolation**: Three-layer protection (scopes, validation, auto-assignment)
- **Transaction safety**: Critical operations wrapped in database transactions
- **Audit trails**: Stock and account ledgers provide complete history
- **Modular design**: Feature flags and module system for flexible access control
- **Comprehensive testing**: 80 tests covering core business flows
- **Production-ready**: CI/CD pipeline, dependency auditing, and deployment checks
