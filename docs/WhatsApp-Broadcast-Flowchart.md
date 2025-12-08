# Flowchart WhatsApp Broadcast System

Dokumentasi visual untuk sistem broadcast WhatsApp menggunakan Fonnte API.

## 1. Main Flow - Complete System

```mermaid
flowchart TD
    Start([Guru Input/Update<br/>Monthly Report]) --> Save[Simpan ke Database]
    Save --> Observer{Observer<br/>MonthlyReportObserver<br/>Detect Change}

    Observer -->|created| Validate
    Observer -->|updated & catatan changed| Validate
    Observer -->|other events| End1([Skip])

    Validate{Validasi<br/>Catatan}
    Validate -->|Catatan kosong| End2([Skip: No Message])
    Validate -->|Mengandung 'draft'| End3([Skip: Still Draft])
    Validate -->|Valid| CheckSent

    CheckSent{Sudah Pernah<br/>Dikirim?}
    CheckSent -->|Yes| End4([Skip: Already Sent])
    CheckSent -->|No| CheckEnabled

    CheckEnabled{Broadcast<br/>Enabled?}
    CheckEnabled -->|WHATSAPP_BROADCAST_ENABLED=false| End5([Skip: Feature Disabled])
    CheckEnabled -->|true| CheckPhone

    CheckPhone{Validasi<br/>Nomor Telepon}
    CheckPhone -->|No Phone| End6([Skip: No Phone Number])
    CheckPhone -->|Invalid Format| End7([Skip: Invalid Phone])
    CheckPhone -->|Valid| CreateRecord

    CreateRecord[Create Broadcast Record<br/>Status: pending]
    CreateRecord --> DispatchJob[Dispatch Job ke Queue<br/>SendMonthlyReportWhatsAppJob]

    DispatchJob --> Queue[Queue System<br/>Process Job]
    Queue --> JobHandle{Job Handle<br/>Method}

    JobHandle --> ValidateInJob{Validate Phone<br/>in Job}
    ValidateInJob -->|Invalid| JobFailed
    ValidateInJob -->|Valid| FormatMsg

    FormatMsg[Format Message<br/>WhatsAppNotificationService]
    FormatMsg --> SendAPI[Send via Fonnte API<br/>POST /send]

    SendAPI --> CheckResponse{API Response}
    CheckResponse -->|Success| MarkSent[Mark as Sent<br/>sent_at = now]
    CheckResponse -->|Failed| CheckRetry

    CheckRetry{Retry Count<br/>< 3?}
    CheckRetry -->|Yes| RetryJob[Retry Job<br/>Backoff 60s]
    CheckRetry -->|No| JobFailed

    RetryJob --> Queue

    MarkSent --> LogSuccess[Log Success Response<br/>to Database]
    LogSuccess --> End8([Success])

    JobFailed[Mark as Failed<br/>Save Error Message]
    JobFailed --> NotifyAdmin[Notify Super Admin<br/>Filament Notification]
    NotifyAdmin --> End9([Failed])

    style Start fill:#e1f5e1
    style End8 fill:#e1f5e1
    style End9 fill:#ffe1e1
    style End1 fill:#fff4e1
    style End2 fill:#fff4e1
    style End3 fill:#fff4e1
    style End4 fill:#fff4e1
    style End5 fill:#fff4e1
    style End6 fill:#fff4e1
    style End7 fill:#fff4e1
    style Queue fill:#e1e5ff
    style SendAPI fill:#ffe1f5
```

## 2. Observer Flow - Trigger Detection

```mermaid
flowchart LR
    A[MonthlyReportObserver] --> B{Event Type}
    B -->|created| C[handleWhatsAppBroadcast]
    B -->|updated| D{catatan field<br/>changed?}
    D -->|Yes| C
    D -->|No| E([Skip])

    C --> F{Validation}
    F -->|catatan empty| G([Skip])
    F -->|catatan contains 'draft'| H([Skip])
    F -->|Already sent| I([Skip])
    F -->|No phone| J([Skip])
    F -->|All pass| K[Create Record<br/>+ Dispatch Job]

    style A fill:#e1f5e1
    style K fill:#e1f5e1
    style E fill:#fff4e1
    style G fill:#fff4e1
    style H fill:#fff4e1
    style I fill:#fff4e1
    style J fill:#fff4e1
```

## 3. Job Processing Flow - Queue Worker

```mermaid
flowchart TD
    Start([Job Dispatched]) --> Pull[Queue Worker<br/>Pull Job]
    Pull --> Handle[handle Method]

    Handle --> V1{Validate Phone}
    V1 -->|Invalid| Fail1[Throw Exception]
    V1 -->|Valid| Format

    Format[Format Message<br/>WhatsAppNotificationService]
    Format --> Send[Send via Fonnte API]

    Send --> Response{HTTP Response}
    Response -->|200 OK| ParseJSON{Parse JSON}
    Response -->|4xx/5xx Error| Fail2[Throw Exception]

    ParseJSON -->|status: true| Success[Update Record<br/>status = sent<br/>sent_at = now]
    ParseJSON -->|status: false| Fail3[Throw Exception]

    Success --> Complete([Job Complete])

    Fail1 --> Failed[failed Method]
    Fail2 --> Failed
    Fail3 --> Failed

    Failed --> CheckRetry{Retry < 3?}
    CheckRetry -->|Yes| Retry[Release Job<br/>Backoff 60s]
    CheckRetry -->|No| FinalFail[Mark Failed<br/>Notify Admin]

    Retry --> Pull
    FinalFail --> End([Job Failed])

    style Start fill:#e1f5e1
    style Complete fill:#e1f5e1
    style End fill:#ffe1e1
    style Send fill:#ffe1f5
```

## 4. Service Layer Flow - WhatsAppNotificationService

```mermaid
flowchart TD
    A[WhatsAppNotificationService] --> B{Method Called}

    B -->|sendWhatsApp| C[sendWhatsApp Method]
    B -->|formatMonthlyReportMessage| D[formatMonthlyReportMessage]
    B -->|validatePhoneNumber| E[validatePhoneNumber]
    B -->|isBroadcastEnabled| F[isBroadcastEnabled]

    C --> C1[Check FONNTE_TOKEN]
    C1 -->|Empty| C2[Throw Exception]
    C1 -->|Valid| C3[HTTP POST to Fonnte]
    C3 --> C4{Response}
    C4 -->|Success| C5[Return Response Array]
    C4 -->|Failed| C6[Throw Exception]

    D --> D1[Get Sekolah Data]
    D1 --> D2[Build Message Template]
    D2 --> D3[Replace Variables:<br/>- nama_siswa<br/>- bulan<br/>- catatan<br/>- website<br/>- nama_sekolah]
    D3 --> D4[Return Formatted String]

    E --> E1{Phone Empty?}
    E1 -->|Yes| E2[Return null]
    E1 -->|No| E3[Convert 0xxx to 62xxx]
    E3 --> E4[Remove non-numeric]
    E4 --> E5{Valid Format?}
    E5 -->|Yes| E6[Return Validated Phone]
    E5 -->|No| E7[Return null]

    F --> F1[Check ENV:<br/>WHATSAPP_BROADCAST_ENABLED]
    F1 -->|true| F2[Return true]
    F1 -->|false/empty| F3[Return false]

    style A fill:#e1f5e1
    style C5 fill:#e1f5e1
    style D4 fill:#e1f5e1
    style E6 fill:#e1f5e1
    style F2 fill:#e1f5e1
    style C2 fill:#ffe1e1
    style C6 fill:#ffe1e1
    style E2 fill:#fff4e1
    style E7 fill:#fff4e1
    style F3 fill:#fff4e1
```

## 5. Database Schema Flow

```mermaid
erDiagram
    MONTHLY_REPORTS ||--o{ MONTHLY_REPORT_BROADCASTS : triggers
    DATA_SISWA ||--o{ MONTHLY_REPORT_BROADCASTS : receives
    SEKOLAH ||--|| MONTHLY_REPORTS : provides_context

    MONTHLY_REPORTS {
        bigint id PK
        bigint data_siswa_id FK
        string month
        text catatan
        timestamp created_at
        timestamp updated_at
    }

    MONTHLY_REPORT_BROADCASTS {
        bigint id PK
        bigint monthly_report_id FK
        bigint data_siswa_id FK
        string phone_number
        text message
        enum status
        text response
        text error_message
        integer retry_count
        timestamp sent_at
        timestamp created_at
    }

    DATA_SISWA {
        bigint id PK
        string nama_lengkap
        string no_telp_ortu_wali
        timestamp created_at
    }

    SEKOLAH {
        bigint id PK
        string nama_sekolah
        string website
        timestamp created_at
    }
```

## 6. State Machine - Broadcast Status

```mermaid
stateDiagram-v2
    [*] --> Pending: Create Record
    Pending --> Processing: Queue Worker Pick

    Processing --> Sent: API Success
    Processing --> Retrying: API Failed (retry < 3)
    Processing --> Failed: API Failed (retry = 3)

    Retrying --> Processing: Backoff 60s

    Sent --> [*]
    Failed --> [*]: Notify Admin

    note right of Pending
        status = 'pending'
        retry_count = 0
        sent_at = null
    end note

    note right of Sent
        status = 'sent'
        sent_at = timestamp
        response = JSON
    end note

    note right of Failed
        status = 'failed'
        error_message = text
        retry_count = 3
    end note
```

## 7. Sequence Diagram - Complete Interaction

```mermaid
sequenceDiagram
    participant G as Guru (Filament)
    participant O as Observer
    participant DB as Database
    participant Q as Queue
    participant J as Job
    participant S as Service
    participant F as Fonnte API
    participant A as Admin

    G->>DB: Save Monthly Report
    DB->>O: Trigger created/updated event

    O->>O: Validate (catatan, draft, sent)
    O->>S: Check isBroadcastEnabled()
    S-->>O: true/false

    alt Broadcast Enabled & Valid
        O->>S: validatePhoneNumber(siswa)
        S-->>O: validated phone

        O->>DB: Create Broadcast Record (pending)
        O->>Q: Dispatch Job

        Q->>J: Process Job (async)
        J->>S: validatePhoneNumber()
        S-->>J: validated

        J->>S: formatMonthlyReportMessage()
        S->>DB: Get Sekolah data
        DB-->>S: sekolah info
        S-->>J: formatted message

        J->>S: sendWhatsApp(phone, message)
        S->>F: POST /send
        F-->>S: HTTP Response

        alt Success
            S-->>J: success response
            J->>DB: Update status = sent
            J-->>Q: Job Complete
        else Failed (retry < 3)
            S-->>J: throw exception
            J->>Q: Release with backoff
            Q->>J: Retry after 60s
        else Failed (retry = 3)
            S-->>J: throw exception
            J->>DB: Update status = failed
            J->>A: Send Notification
            A-->>J: Notification sent
            J-->>Q: Job Failed
        end
    else Broadcast Disabled or Invalid
        O->>O: Skip broadcast
    end
```

## 8. Testing Flow

```mermaid
flowchart TD
    Start([Run Test Command]) --> Command[php artisan test:whatsapp-broadcast]

    Command --> Parse{Parse Arguments}
    Parse -->|No ID| Random[Get Random<br/>Monthly Report]
    Parse -->|With ID| GetSpecific[Get Specific<br/>Report by ID]

    Random --> Validate
    GetSpecific --> Validate

    Validate{Report Found?}
    Validate -->|No| Error1([Error: Not Found])
    Validate -->|Yes| CheckFeature

    CheckFeature[Check Feature Status]
    CheckFeature --> ShowInfo[Display Info:<br/>- Report Details<br/>- Siswa Info<br/>- Phone Number<br/>- Message Preview]

    ShowInfo --> Confirm{User Confirm<br/>Real Send?}
    Confirm -->|No| DryRun([Dry Run Complete])
    Confirm -->|Yes| RealSend[Call sendWhatsApp]

    RealSend --> API{API Response}
    API -->|Success| Success([Success Message])
    API -->|Failed| Error2([Error Message])

    style Start fill:#e1f5e1
    style DryRun fill:#e1f5e1
    style Success fill:#e1f5e1
    style Error1 fill:#ffe1e1
    style Error2 fill:#ffe1e1
```

## 9. Configuration Dependencies

```mermaid
graph LR
    A[.env File] --> B[FONNTE_API_TOKEN]
    A --> C[WHATSAPP_BROADCAST_ENABLED]
    A --> D[QUEUE_CONNECTION]

    B --> E[WhatsAppNotificationService]
    C --> E

    D --> F[Queue System]
    F --> G[database]
    F --> H[redis]
    F --> I[sync]

    E --> J[Fonnte API]
    J --> K[POST /send]

    L[Database] --> M[monthly_reports]
    L --> N[monthly_report_broadcasts]
    L --> O[data_siswa]
    L --> P[sekolah]

    Q[Queue Worker] --> F
    Q --> R[Supervisor Config]

    style A fill:#ffe1e1
    style E fill:#e1f5e1
    style J fill:#ffe1f5
    style L fill:#e1e5ff
```

## 10. Error Handling Flow

```mermaid
flowchart TD
    Start[Error Occurred] --> Type{Error Type}

    Type -->|No Phone| E1[Log: Skip - No Phone]
    Type -->|Invalid Phone| E2[Log: Skip - Invalid Phone]
    Type -->|API Failed| E3[Catch Exception]
    Type -->|Token Empty| E4[Throw Exception]
    Type -->|Network Error| E5[Catch Exception]

    E1 --> Skip1([Skip Silently])
    E2 --> Skip2([Skip Silently])

    E3 --> Retry{Retry Count}
    E5 --> Retry

    Retry -->|< 3| Backoff[Release Job<br/>Backoff 60s]
    Retry -->|= 3| Failed[Mark Failed]

    Backoff --> Queue[Back to Queue]

    Failed --> Log[Log Error to DB]
    Log --> Notify[Notify Admin]
    Notify --> End([Failed Permanently])

    E4 --> Fatal([Fatal Error])

    style Start fill:#ffe1e1
    style Skip1 fill:#fff4e1
    style Skip2 fill:#fff4e1
    style End fill:#ffe1e1
    style Fatal fill:#ff0000,color:#fff
```

---

## Legend

-   🟢 **Hijau**: Start/Success state
-   🔴 **Merah**: Error/Failed state
-   🟡 **Kuning**: Skip/Warning state
-   🔵 **Biru**: Processing/Queue state
-   🟣 **Ungu**: External API call

## Cara Menggunakan Flowchart

1. **Main Flow**: Gunakan untuk memahami alur lengkap dari input guru hingga WhatsApp terkirim
2. **Observer Flow**: Fokus pada trigger detection dan validasi awal
3. **Job Processing**: Detail proses queue worker dan retry mechanism
4. **Service Layer**: Memahami method-method dalam WhatsAppNotificationService
5. **Database Schema**: Struktur relasi antar tabel
6. **State Machine**: Status lifecycle dari broadcast record
7. **Sequence Diagram**: Interaksi antar komponen secara temporal
8. **Testing Flow**: Alur command testing
9. **Configuration**: Dependency antar komponen
10. **Error Handling**: Berbagai skenario error dan penanganannya

## Tools untuk View

Diagram ini menggunakan **Mermaid syntax** yang dapat di-render di:

-   ✅ GitHub (native support)
-   ✅ GitLab (native support)
-   ✅ VS Code (dengan extension "Markdown Preview Mermaid Support")
-   ✅ Online: https://mermaid.live/
-   ✅ Obsidian
-   ✅ Notion

---

**Created:** 28 Oktober 2025  
**Version:** 1.0.0
