<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        h2 {
            color: #2c3e50;
        }
        p {
            margin-bottom: 10px;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            margin-bottom: 5px;
        }
        .footer {
            margin-top: 20px;
            font-size: 14px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <h2>Leave Notification: Your Leave Status in HR Management System</h2>

    <p>Dear {{ $employee_name }},</p>

    <p>I hope this email finds you well.</p>

    <p>This is to inform you that your leave has been marked in the HR Management System. Below are the details of the leave for your reference:</p>

    <ul>
        <li><strong>Employee Name:</strong> {{ $employee_name }}</li>
        <li><strong>Employee ID:</strong> {{ $employee_id }}</li>
        <li><strong>Leave Type:</strong> {{ $leave_type }}</li>
        <li><strong>Leave Duration:</strong> {{ $start_date }} to {{ $end_date }}</li>
        <li><strong>Total Days:</strong> {{ $total_days }}</li>
        <li><strong>Reason (if provided):</strong> {{ $reason ?? 'N/A' }}</li>
    </ul>

    <p>Please ensure that your leave information is accurate. If there are any discrepancies or updates required, feel free to contact the HR department at <strong>{{ $hr_email }}</strong> or <strong>{{ $hr_contact_number }}</strong>.</p>

    <p>We encourage you to keep track of your remaining leave balance through the HR portal: <a href="{{ $hr_portal_link }}">{{ $hr_portal_link }}</a>.</p>

    <p>Thank you for your cooperation.</p>

    <div class="footer">
        <p>Best regards,</p>
        <p>HR Management Team</p>
    </div>
</body>
</html>
