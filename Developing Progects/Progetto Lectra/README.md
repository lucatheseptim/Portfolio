# Order Management Backoffice — PHP

Internal backoffice for managing garment cutting orders, developed during an
internship. The system covers the order lifecycle from data entry to shipment,
integrating with the company's legacy ERP and with a courier API.

## What it does

- **Order entry** — a guided multi-step flow: authentication, order form,
  garment summary, shipping details, confirmation
- **Order management** — back office views listing orders and cutting batches
- **Shipping** — integration with the DHL API to create shipments and labels
- **Documents** — generation of the working order (bolla di lavorazione)
- **Notifications** — e-mail confirmations sent to the customer and to
  internal recipients
- **Scheduled processing** — a cron entry point that dispatches pending orders
- **Data import** — CSV import pipeline for bulk data

## Technology stack

| | |
|---|---|
| Language | PHP |
| Database | MySQL, plus connectivity to an IBM AS/400 legacy ERP |
| Integration | REST/JSON and SOAP web services, courier API |
| Front end | HTML, CSS, JavaScript (jQuery) |
| Testing | PHPUnit |
| Version control | Subversion (SVN) |

The codebase predates Composer in this context: classes are wired with explicit
`require_once` includes and organised in directories by responsibility.

## Project structure

```
Controller/          OrderController, DhlController
Services/            Services, BollaLavorazioneService
utils/               ApiCaller, Model, Req, Campaign, WSSoapClient, Field, User
view/
  LectraOrdine/      the order flow: login, form, summary, shipping, confirmation
  LectraBackoffice/  the back office: order and cutting lists
include/             bootstrap, configuration, database connectors
tests/               PHPUnit suite
import/              CSV import pipeline
```

## Design notes

- **Layered separation.** Controllers handle the request, services hold the
  business rules (order creation, working order generation), utilities wrap the
  integration points (HTTP calls, SOAP client, persistence).
- **Test coverage over the critical paths.** The suite exercises controllers,
  services, models and request handling: `OrderControllerTest`,
  `DhlControllerTest`, `BollaLavorazioneServiceTest`, `OrderModelTest`,
  `SaveOrderTest`, `LectraAuthServiceTest`, `LectraOrderServiceTest`, `ReqTest`.
- **Legacy integration.** The ERP is reached over ODBC; the SOAP client wraps
  the external web service, so that a change of endpoint does not propagate
  into the business layer.

## Testing

```bash
phpunit tests/
```

The tests are written against the service and controller interfaces, with
factories for the test data.

## Security note

Configuration (database and legacy-system credentials) lives in
`include/config.php`. In a public version of this repository that file should
be **replaced by a template** reading values from environment variables, and
excluded from version control. Credentials should never be committed.

## Author

Luca Airoldi — internship project
