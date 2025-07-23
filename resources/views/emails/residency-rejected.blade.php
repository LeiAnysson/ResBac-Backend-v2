<!DOCTYPE html>
<html>
<head>
    <title>ResBac - Residency Rejected</title>
</head>
<body>
    <h2>Hello {{ $user->first_name }}!</h2>

    <p>❌ We're sorry to inform you that your residency verification has been <strong>rejected</strong>.</p>

    <p>This may be because your uploaded ID:</p>
    <ul>
        <li>Was unclear or unreadable</li>
        <li>Did not show a valid Bocaue address</li>
        <li>Was not a valid government-issued ID</li>
    </ul>

    <p>You may log in and upload a new ID for re-verification.</p>

    <p>Thank you for understanding, and stay safe.</p>

    <br>
    <small>This is an automated email. Please do not reply.</small>
</body>
</html>
