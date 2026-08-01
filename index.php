<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Email Sender</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="tabs main-tabs">
        <button class="tab active" id="tabCompose" type="button">Compose</button>
        <button class="tab" id="tabScheduled" type="button">Scheduled</button>
        <button class="tab" id="tabSent" type="button">Sent</button>
      </div>
      <div class="auth" id="authBox"></div>
    </div>

    <section class="panel" id="composePage">
      <div class="tabs input-tabs">
        <button class="tab active" id="tabForm" type="button">Form</button>
        <button class="tab" id="tabCsv" type="button">CSV</button>
      </div>

      <div id="formPanel">
        <div class="table-wrap" id="formTableWrap"></div>
        <div class="actions">
          <button class="btn btn-primary" id="scheduleBtn" type="button" disabled>Schedule in Outlook</button>
        </div>
      </div>

      <div id="csvPanel" hidden>
        <div class="drop" id="drop">
          <strong>Drop CSV here or click to choose</strong>
          <span>to_email, company, industry, demo_url, scheduled_at</span>
          <input type="file" id="file" accept=".csv,text/csv" hidden>
        </div>
        <div class="table-wrap" id="csvTableWrap" hidden></div>
        <div class="actions">
          <button class="btn btn-primary" id="scheduleCsvBtn" type="button" disabled>Schedule CSV in Outlook</button>
        </div>
      </div>

      <div class="status" id="statusMsg"></div>

      <div class="preview-stack" id="emailPreview">
        <p class="empty">Start filling the table to preview emails.</p>
      </div>
    </section>

    <section class="panel" id="scheduledPage" hidden>
      <div class="actions actions-top">
        <button class="btn btn-secondary" id="refreshScheduledBtn" type="button">Refresh</button>
      </div>
      <div class="table-wrap" id="scheduledList"></div>
    </section>

    <section class="panel" id="sentPage" hidden>
      <div class="actions actions-top">
        <button class="btn btn-secondary" id="refreshSentBtn" type="button">Refresh</button>
      </div>
      <div class="table-wrap" id="sentList"></div>
    </section>
  </div>

  <script src="assets/app.js"></script>
</body>
</html>
