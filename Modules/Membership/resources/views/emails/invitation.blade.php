<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Invitation to {{ $tenantName }}</title>
</head>
<body style="margin:0; padding:0; background:#f3f6fb; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif; color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f6fb; padding:28px 12px;">
	<tr>
		<td align="center">
			<table role="presentation" width="640" cellpadding="0" cellspacing="0" style="width:100%; max-width:640px; background:#ffffff; border:1px solid #dbe4f0; border-radius:18px; overflow:hidden;">
				<tr>
					<td style="padding:28px 28px 18px; background:linear-gradient(120deg, #ffffff 0%, #f8fbff 100%); border-bottom:1px solid #e8eef7;">
						<p style="margin:0 0 10px; font-size:12px; letter-spacing:0.08em; text-transform:uppercase; color:#64748b; font-weight:700;">Workspace invitation</p>
						<h1 style="margin:0; font-size:30px; line-height:1.2; color:#0f172a;">You have been invited</h1>
					</td>
				</tr>

				<tr>
					<td style="padding:24px 28px;">
						<p style="margin:0 0 16px; font-size:18px; line-height:1.6; color:#1e293b;">
							<strong style="color:#0f172a;">{{ $inviterName }}</strong>
							invited you to join
							<strong style="color:#0f172a;">{{ $tenantName }}</strong>
							as
							<strong style="text-transform:capitalize; color:#0f172a;">{{ $role }}</strong>.
						</p>

						<table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px 0 18px;">
							<tr>
								<td align="center" style="border-radius:10px; background:#0f172a;">
									<a href="{{ $acceptUrl }}" style="display:inline-block; padding:13px 20px; color:#ffffff; text-decoration:none; font-size:15px; font-weight:700;">
										Accept invitation
									</a>
								</td>
							</tr>
						</table>

						<p style="margin:0 0 16px; font-size:13px; color:#64748b; line-height:1.6;">
							If the button does not work, copy and open this link in your browser:<br>
							<a href="{{ $acceptUrl }}" style="color:#1d4ed8; text-decoration:underline; word-break:break-all;">{{ $acceptUrl }}</a>
						</p>

						<div style="margin:0 0 18px; padding:12px 14px; border:1px solid #f7d7a6; background:#fffbeb; border-radius:10px;">
							<p style="margin:0; font-size:13px; line-height:1.5; color:#92400e;">
								Invitation expires at: <strong>{{ optional($expiresAt)->toDateTimeString() }}</strong>
							</p>
						</div>

						<p style="margin:0; font-size:15px; color:#334155; line-height:1.6;">
							Thanks,<br>
							<strong style="color:#0f172a;">{{ config('app.name') }}</strong>
						</p>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</body>
</html>
