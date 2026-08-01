# Email Sender

Upload a CSV, personalize HTML outreach emails, and schedule them with Outlook **Send later** via Microsoft Graph.

## Setup

### 1. Azure app

1. Go to [Azure Portal → App registrations](https://portal.azure.com/#view/Microsoft_AAD_RegisteredApps/ApplicationsListBlade) → **New registration**
2. Name it (e.g. `Email Sender`)
3. Supported account types: your preference (`Personal Microsoft accounts` and/or work/school)
4. Redirect URI (Web):
   ```
   http://localhost/Projects/email-sender/api/callback.php
   ```
5. After create:
   - Copy **Application (client) ID**
   - **Certificates & secrets** → New client secret → copy the value
   - **API permissions** → Microsoft Graph → Delegated:
     - `User.Read`
     - `Mail.ReadWrite`
     - `Mail.Send`
   - Click **Grant admin consent** if your tenant requires it

### 2. Config

```bash
copy .env.example .env
```

Edit `.env`:

```
MICROSOFT_CLIENT_ID=your-client-id
MICROSOFT_CLIENT_SECRET=your-client-secret
MICROSOFT_TENANT_ID=common
APP_URL=http://localhost/Projects/email-sender
TIMEZONE=Europe/Oslo
```

Use your real tenant ID instead of `common` if you only use a work/school account.

### 3. Run

Open:

```
http://localhost/Projects/email-sender/
```

1. Click **Connect Microsoft**
2. Use **Form** (editable table) or upload a **CSV**
3. Check the previews below the table
4. Click **Schedule in Outlook**
5. Check **Scheduled** / **Sent** tabs (or Outlook itself)

## CSV format

```csv
to_email,company,industry,demo_url,scheduled_at
kontakt@example.com,Example AS,bygg og anlegg,https://example.com/demo,2026-08-10 09:00
```

`scheduled_at` uses `TIMEZONE` from `.env` (`Europe/Oslo` by default).

## Notes

- Subject is `Samarbeid` so Outlook lists can identify emails from this app
- Edit the email body in `templates/email.html`
- Token is stored in `data/token.json` (gitignored)
