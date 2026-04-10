<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Invitation to {{ $tenantName }}</title>
</head>
<body>
<p>You have been invited.</p>

<p>
	{{ $inviterName }} invited you to join <strong>{{ $tenantName }}</strong>
	as <strong>{{ $role }}</strong>.
</p>

<p>
	Preview and accept invitation: <a href="{{ $acceptUrl }}">{{ $acceptUrl }}</a>
</p>

<p>Invitation expires at: {{ optional($expiresAt)->toDateTimeString() }}</p>

<p>Thanks,<br>{{ config('app.name') }}</p>
</body>
</html>
