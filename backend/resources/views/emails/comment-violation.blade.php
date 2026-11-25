<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemberitahuan Pelanggaran Etika &amp; Pengurangan Poin</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f7f5; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color:#142c14;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px; background:#ffffff; border-radius:16px; border:1px solid #e5ece7; overflow:hidden; box-shadow:0 8px 24px rgba(20,44,20,0.06);">
                    <tr>
                        <td style="background:linear-gradient(135deg, #142c14 0%, #1e8c45 100%); padding:24px 28px; color:#ffffff;">
                            <div style="font-size:22px; font-weight:800; letter-spacing:-0.02em;">GrowHub</div>
                            <div style="font-size:12px; opacity:0.88; margin-top:4px; letter-spacing:0.04em; text-transform:uppercase;">Qirin &amp; Co Learning Hub</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 12px; font-size:15px;">Halo <strong>{{ $user->name }}</strong> (NIP: {{ $user->nip }}),</p>
                            <p style="margin:0 0 18px; font-size:14px; line-height:1.6; color:#4a5d4e;">
                                Komentar Anda pada konten forum <strong>"{{ $contentTitle }}"</strong> telah dihapus oleh Administrator karena terindikasi melanggar Etika Komunikasi GrowHub.
                            </p>

                            <div style="background:#f7faf8; border:1px solid #e5ece7; border-radius:12px; padding:14px 16px; margin-bottom:16px;">
                                <div style="font-size:11px; text-transform:uppercase; letter-spacing:0.06em; color:#8a9a8e; font-weight:700; margin-bottom:6px;">
                                    Komentar yang Dihapus
                                </div>
                                <div style="font-size:13px; color:#142c14; line-height:1.5; font-style:italic;">
                                    "{{ \Illuminate\Support\Str::limit($commentBody, 300) }}"
                                </div>
                            </div>

                            <div style="background:#fdecee; border:1px solid #fecaca; border-radius:12px; padding:16px; margin-bottom:18px;">
                                <div style="font-size:11px; text-transform:uppercase; letter-spacing:0.06em; color:#b91c1c; font-weight:700; margin-bottom:6px;">
                                    Alasan Pelanggaran
                                </div>
                                <div style="font-size:15px; font-weight:800; color:#991b1b; margin-bottom:4px;">
                                    {{ $policy->title }}
                                </div>
                                <div style="font-size:13px; color:#7f1d1d; line-height:1.5; margin-bottom:12px;">
                                    {{ $policy->description }}
                                </div>
                                <div style="display:inline-block; background:#ffffff; border:1px solid #fca5a5; border-radius:8px; padding:6px 12px; font-size:13px; font-weight:800; color:#b91c1c;">
                                    Pengurangan Poin: -{{ $pointsDeducted }} Poin
                                </div>
                            </div>

                            <p style="margin:0 0 16px; font-size:13px; line-height:1.6; color:#4a5d4e;">
                                Total poin Anda saat ini: <strong>{{ number_format($remainingPoints, 0, ',', '.') }} Pts</strong>.
                            </p>

                            <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:12px 14px; font-size:12px; line-height:1.5; color:#92400e;">
                                Mohon perhatikan kembali daftar Etika Komunikasi GrowHub saat berdiskusi agar tidak terjadi pelanggaran berulang.
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px; background:#f7faf8; border-top:1px solid #e5ece7; font-size:12px; color:#8a9a8e;">
                            Email ini dikirim otomatis oleh sistem GrowHub.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

