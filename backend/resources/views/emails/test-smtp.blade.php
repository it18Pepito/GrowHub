<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Coba Konfigurasi Email SMTP - GrowHub</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f7f5; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color:#142c14;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px; background:#ffffff; border-radius:16px; border:1px solid #e5ece7; overflow:hidden; box-shadow:0 8px 24px rgba(20,44,20,0.06);">
                    <tr>
                        <td style="background:linear-gradient(135deg, #142c14 0%, #1e8c45 100%); padding:24px 28px; color:#ffffff;">
                            <div style="font-size:22px; font-weight:800; letter-spacing:-0.02em;">GrowHub</div>
                            <div style="font-size:12px; opacity:0.88; margin-top:4px; letter-spacing:0.04em; text-transform:uppercase;">Administration SMTP Test</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <div style="display:inline-block; background:#e8f9ee; color:#15803d; border:1px solid #bbf7d0; border-radius:8px; padding:6px 14px; font-size:13px; font-weight:700; margin-bottom:16px;">
                                &#10003; Koneksi SMTP Berhasil Terhubung
                            </div>

                            <p style="margin:0 0 14px; font-size:15px; font-weight:700; color:#142c14;">
                                Halo Administrator,
                            </p>

                            <p style="margin:0 0 20px; font-size:14px; line-height:1.6; color:#4a5d4e;">
                                Ini adalah email konfirmasi pengujian server email keluar (SMTP) dari sistem <strong>GrowHub</strong>. Pengaturan SMTP yang Anda masukkan telah berhasil diverifikasi dan siap digunakan untuk pengiriman kode OTP reset password dan email notifikasi sistem.
                            </p>

                            <div style="background:#f7faf8; border:1px solid #e5ece7; border-radius:12px; padding:16px; margin-bottom:20px;">
                                <div style="font-size:11px; text-transform:uppercase; letter-spacing:0.06em; color:#8a9a8e; font-weight:700; margin-bottom:10px;">
                                    Detail Parameter SMTP Saat Pengujian
                                </div>
                                <table width="100%" cellspacing="0" cellpadding="4" style="font-size:13px; color:#142c14;">
                                    <tr>
                                        <td width="40%" style="color:#64748b;">Mailer Driver:</td>
                                        <td width="60%" style="font-weight:700;">{{ $smtpDetails['mailer'] ?? 'smtp' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color:#64748b;">Host Server:</td>
                                        <td style="font-weight:700;">{{ $smtpDetails['host'] ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color:#64748b;">Port &amp; Scheme:</td>
                                        <td style="font-weight:700;">{{ $smtpDetails['port'] ?? '-' }} ({{ $smtpDetails['scheme'] ?? 'none' }})</td>
                                    </tr>
                                    <tr>
                                        <td style="color:#64748b;">Username:</td>
                                        <td style="font-weight:700;">{{ $smtpDetails['username'] ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color:#64748b;">Alamat Pengirim:</td>
                                        <td style="font-weight:700;">{{ $smtpDetails['from_address'] ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color:#64748b;">Nama Pengirim:</td>
                                        <td style="font-weight:700;">{{ $smtpDetails['from_name'] ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color:#64748b;">Waktu Uji Coba:</td>
                                        <td style="font-weight:700;">{{ $testedAt }}</td>
                                    </tr>
                                </table>
                            </div>

                            <p style="margin:0; font-size:12px; color:#8a9a8e; line-height:1.5;">
                                Permintaan uji coba diinisiasi oleh Administrator: <strong>{{ $testedBy }}</strong>.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px; background:#f7faf8; border-top:1px solid #e5ece7; font-size:12px; color:#8a9a8e;">
                            Email ini dihasilkan secara otomatis oleh Administration Console GrowHub.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
