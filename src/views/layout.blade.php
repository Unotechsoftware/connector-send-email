<!DOCTYPE html>
<html>

<head>
  <meta name="viewport" content="width=device-width">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title>{{$subject}}</title>

  <style>
    body {
    }
    .email-wrapper {
      background-color: white;
      font-size: 15px;
      font-family: Arial, Helvetica, sans-serif;
    }

    .header {
      text-align: center;
    }

    .email-body-wrapper {
      padding: 20px;
      /* display: flex; */
    }

    .center-block {
      width: auto;
      max-width: 700px;
      margin-left: auto;
      margin-right: auto
    }

    .container {
      color: black;
    }

    a.text-light {
      text-decoration: none;
      color: #f8f9fa;
    }

    a.text-light:hover {
      text-decoration: underline;
      color: #dae0e5;
    }

    .row {
      /* display: flex; */
      width: 100%;
      clear: both;
      overflow: auto;
    }

    .row>div {
      width: 50%;
      float: left;
    }

    .form-group {
      padding-bottom: 10px;
    }

    .record-list table {
      width: 100%;
      border-collapse: collapse;
    }

    .record-list td {
      padding: 3px;
    }

    .record-list thead {
      text-align: left;
      border-bottom: 1px solid black;
      background-color: #3397e1;
      color: white;
      font-weight: bold;
    }

    .record-list th {
      font-weight: normal;
      padding: 3px;
    }

    .record-list tbody tr {
      text-align: left;
      border-bottom: 1px solid lightgray;
    }

    @media (max-width: 500px) {

      .email-body-wrapper {
        padding: 0;
      }
      .center-block {
        width: auto;
        min-width: auto;
      }
    }
  </style>

</head>

<body>
  <div class="email-wrapper">
    <div class="container">
      <div class="email-body-wrapper">
        <div class="email-body center-block">
          {!! $body !!}
        </div>
      </div>
      <br clear="all">
      <!-- <hr> -->
      <!-- <div align="center">
        Sent with ♥️ by <a href="https://www.processmaker.com">ProcessMaker 4</a> on {{date('Y-m-d H:i:s')}}
        <br><br>
        <small>&copy; {{date('Y')}} ProcessMaker Inc.</small>
      </div> -->
    </div>
  </div>
</body>

</html>
