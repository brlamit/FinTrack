<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Group Invitation</title>
</head>
<body>
    <h1>You've been added to "{{ $group->name }}" on FinTrack!</h1>

    <p>Hi {{ $user->name }},</p>

    <p>You have been invited to join the group "{{ $group->name }}" on FinTrack.</p>

    @if($password)
        <h2>Login Details:</h2>
        <ul>
            <li><strong>Username:</strong> {{ $user->username }}</li>
            <li><strong>Password:</strong> {{ $password }}</li>
        </ul>

        <p><strong>Download the App:</strong></p>
        <ul>
            <li>Android: <a href="#">[Link]</a></li>
            <li>iOS: <a href="#">[Link]</a></li>
        </ul>

        <p>Please change your password after first login.</p>
    @else
        <p>You can now access the group using your existing credentials.</p>
        
        <p><strong>Download the App:</strong></p>
        <ul>
            <li>Android: <a href="#">[Link]</a></li>
            <li>iOS: <a href="#">[Link]</a></li>
        </ul>
    @endif

    <p>— FinTrack Team</p>
</body>
</html>