<!DOCTYPE html>
<html>

<body style="font-family: sans-serif; color: #111827; padding: 24px; background:#f9fafb;">
    <div
        style="max-width:560px; margin:0 auto; background:#fff; border-radius:8px; padding:32px; border:1px solid #e5e7eb;">
        @if ($shopName)
            <p style="color:#6b7280; font-size:12px; text-transform:uppercase; letter-spacing:0.05em; margin:0 0 8px;">
                {{ $shopName }}</p>
        @endif
        <h2 style="margin:0 0 16px; font-size:18px;">{{ $mailSubject }}</h2>
        <p style="white-space: pre-line; line-height:1.6; font-size:14px; color:#374151;">{{ $bodyText }}</p>
        @if ($actionUrl)
            <p style="margin-top:24px;">
                <a href="{{ $actionUrl }}"
                    style="background:#2563eb;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;display:inline-block;font-size:14px;">
                    {{ $actionLabel ?? 'View' }}
                </a>
            </p>
        @endif
        <p style="margin-top:32px; font-size:11px; color:#9ca3af;">This is an automated notification. Please do not reply
            to this email.</p>
    </div>
</body>

</html>
