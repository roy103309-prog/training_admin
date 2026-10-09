/**
 * Certificate print layout (from the New Life admin: buildCertificateHtml).
 * The institute name, address, contact line, logo and verification link
 * come from the institute's own settings (CertificatePrint.setInstitute).
 * Usage: CertificatePrint.open(entry, student)
 *   entry   = { issueDate, title, number, course_name, metadata }
 *   student = { student_id, name, photo, father_name, mother_name, date_of_birth, passport_number, course_name, course_category }
 */
(function (window) {
    'use strict';

    var INSTITUTE = { name: '', address: '', contact: '', logo: '', verifyUrl: '' };

    function absoluteAssetUrl(path) {
        if (!path) return '';
        if (/^(?:https?:)?\/\//i.test(path) || path.indexOf('data:') === 0) return path;
        var url = window.NL ? window.NL.media(path) : path;
        return /^https?:/i.test(url) ? url : window.location.origin + url;
    }

    function formatDisplayDate(value) {
        if (!value) return '—';
        var date = new Date(value);
        if (Number.isNaN(date.getTime())) return value;
        return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function buildCertificateHtml(entry, student) {
        const issueDate = formatDisplayDate(entry.issueDate);
        const metadata = entry && entry.metadata ? entry.metadata : {};
        const metadataDob = metadata.date_of_birth || null;
        const metadataCourse = metadata.course_name || metadata.course_title || metadata.course || null;
        const rawDob = student.date_of_birth
            ? formatDisplayDate(student.date_of_birth)
            : (metadataDob ? formatDisplayDate(metadataDob) : '');
        const normalizedDob = rawDob && rawDob !== '—' ? rawDob : '';
        const courseName = entry.course_name
            || entry.course
            || entry.courseName
            || metadataCourse
            || student.course_name
            || '';
    const studentPhoto = absoluteAssetUrl(student.photo || 'assets/images/avatars/avatar-2.png');
    const companyLogo = absoluteAssetUrl(INSTITUTE.logo);
    const instituteName = escapeHtml(INSTITUTE.name || 'Training Center');

        const fatherName = student.father_name || metadata.father_name || 'Not Provided';
        const motherName = student.mother_name || metadata.mother_name || 'Not Provided';
    const passportNumber = student.passport_number || metadata.passport_number || '';
    const rollNumber = student.student_id || '';
    const courseTemplateText = '';
    const courseCategory = (student.course_category || '').toLowerCase().trim();
    const programType = escapeHtml(courseCategory && courseCategory !== 'other' ? courseCategory : 'training');

        const fatherDisplay = escapeHtml(fatherName);
        const motherDisplay = escapeHtml(motherName);
        const dobDisplay = normalizedDob ? escapeHtml(normalizedDob) : '____________________';
        // Leave course field blank for handwriting after printing
        const courseDisplay = '';
        const passportDisplay = passportNumber ? escapeHtml(passportNumber) : '';
        const rollDisplay = rollNumber ? escapeHtml(rollNumber) : '';

    // Generate QR code URL for certificate verification
        // QR code only when the institute has a website / verification link
        const certificateUrl = INSTITUTE.verifyUrl || '';
        const qrCodeUrl = certificateUrl ? `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(certificateUrl)}` : '';

        return `
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>${escapeHtml(student.student_id || 'certificate')}</title>
                <link rel="preconnect" href="https://fonts.googleapis.com">
                <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Great+Vibes&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
                <style>
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }

                    :root {
                        --page-width: 297mm;
                        --page-height: 210mm;
                        --page-padding: 12mm;
                        --company-name-font-size: 52px;
                        --company-name-font-size-print: var(--company-name-font-size);
                    }

                    html, body {
                        width: 100%;
                        min-height: 100%;
                    }

                    body {
                        font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
                        background: #111827;
                        min-height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        padding: clamp(24px, 4vw, 48px);
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }

                    .certificate-container {
                        width: min(var(--page-width), calc(100vw - 48px));
                        max-width: var(--page-width);
                        max-height: var(--page-height);
                        aspect-ratio: 297 / 210;
                        background: radial-gradient(circle at center, rgba(15, 23, 42, 0.06), rgba(15, 23, 42, 0.02)) #ffffff;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
                        position: relative;
                        overflow: hidden;
                        display: flex;
                        align-items: stretch;
                        justify-content: center;
                        padding: 10mm;
                        box-sizing: border-box;
                        border-radius: 8px;
                    }

                    .certificate-container::before {
                        content: '';
                        position: absolute;
                        inset: 0;
                        background: rgba(255, 255, 255, 0.80);
                        pointer-events: none;
                        z-index: 0;
                    }

                    /* Elegant border design - extends to edges */
                    .certificate-border {
                        position: absolute;
                        inset: 8px;
                        border: 3px solid #1a1a2e;
                        pointer-events: none;
                        z-index: 2;
                    }

                    .certificate-border::before {
                        content: '';
                        position: absolute;
                        inset: -5px;
                        border: 1px solid #c9a961;
                    }

                    .certificate-border::after {
                        content: '';
                        position: absolute;
                        inset: 6px;
                        border: 1px solid #e8dcc4;
                    }

                    .certificate-content {
                        position: relative;
                        padding: 20px 5px;
                        width: 100%;
                        height: 100%;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        z-index: 3;
                        box-sizing: border-box;
                        overflow: hidden;
                        border-radius: 6px;
                    }

                    .certificate-content > * {
                        position: relative;
                        z-index: 2;
                    }

                    .certificate-watermark {
                        position: absolute;
                        inset: 25mm 35mm;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        opacity: 0.20;
                        filter: saturate(160%) contrast(120%) brightness(0.9);
                        mix-blend-mode: multiply;
                        pointer-events: none;
                        z-index: 1;
                    }

                    .certificate-watermark img {
                        width: 78%;
                        max-width: 780px;
                        height: auto;
                        object-fit: contain;
                        filter: drop-shadow(0 12px 30px rgba(15, 23, 42, 0.25));
                    }

                    /* Header section */
                    .certificate-header {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        gap: 15px;
                        margin-bottom: 0;
                        width: 100%;
                    }

                    .header-center {
                        text-align: center;
                        width: 100%;
                        padding: 0;
                    }

                    .company-name {
                        font-family: 'Playfair Display', serif;
                        font-size: var(--company-name-font-size);
                        font-weight: 700;
                        color: #1a1a2e;
                        margin-bottom: 10px;
                        letter-spacing: 1.4px;
                        text-transform: uppercase;
                        text-align: center;
                        width: 100%;
                    }

                    .company-address {
                        font-size: 20px;
                        color: #444;
                        line-height: 1.6;
                        font-weight: 500;
                        text-align: center;
                        width: 100%;
                    }

                    .header-photos {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        gap: 25px;
                        width: 100%;
                        margin-top: -70px;
                    }

                    .logo-container,
                    .student-photo-container {
                        width: 150px;
                        height: 170px;
                        border: 3px solid #c9a961;
                        border-radius: 4px;
                        overflow: hidden;
                        background: #f8f9fa;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }

                    .student-photo-container {
                        padding: 0;
                    }

                    .logo-container img,
                    .student-photo-container img {
                        width: 100%;
                        object-fit: cover;
                    }

                    .student-photo-container img{
                        height: 100%;
                    }

                    /* Certificate title */
                    .certificate-title {
                        text-align: center;
                        margin: -25px 0 18px;
                    }

                    .certificate-word {
                        font-family: 'Great Vibes', 'Cormorant Garamond', serif;
                        font-size: 108px;
                        font-weight: 400;
                        color: #2c3e50;
                        letter-spacing: 2px;
                        text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.15);
                        margin-bottom: 4px;
                        display: inline;
                    }

                    .certificate-subtitle {
                        font-family: 'Great Vibes', 'Cormorant Garamond', serif;
                        font-size: 108px;
                        font-weight: 400;
                        color: #2c3e50;
                        letter-spacing: 2px;
                        text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.15);
                        display: inline;
                        margin-left: 15px;
                    }

                    /* Body text */
                    .certificate-body {
                        flex: 1;
                        display: flex;
                        flex-direction: column;
                        text-align: center;
                        padding: 0 35px 5px;
                        gap: 14px;
                        align-items: center;
                    }

                    .intro-text {
                        font-size: 19px;
                        color: #2a2a2a;
                        font-weight: 600;
                    }

                    .student-name {
                        font-family: 'Playfair Display', serif;
                        font-size: 56px;
                        font-weight: 700;
                        color: #1a1a2e;
                        margin: 6px auto;
                        border-bottom: 2px solid #c9a961;
                        display: inline-block;
                        padding-bottom: 2px;
                    }

                    .family-line {
                        font-size: 19px;
                        color: #1f2937;
                        line-height: 1.4;
                    }

                    .info-row {
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        gap: 16px;
                        font-size: 18px;
                        color: #374151;
                        flex-wrap: nowrap;
                        text-align: center;
                        width: 100%;
                        overflow: hidden;
                    }

                    .info-item {
                        display: flex;
                        align-items: center;
                        gap: 6px;
                        justify-content: center;
                        white-space: nowrap;
                        flex: 1 1 0;
                        min-width: 0;
                    }

                    .statement-text {
                        font-size: 19px;
                        line-height: 1.75;
                        color: #1f2937;
                        text-align: center;
                    }

                    .identity-row {
                        display: flex;
                        justify-content: center;
                        gap: 40px;
                        flex-wrap: wrap;
                        text-align: center;
                    }

                    .course-detail {
                        flex-direction: column;
                        gap: 6px;
                    }

                    .course-template-text {
                        font-size: 20px;
                        font-weight: 700;
                        color: #1f2937;
                        letter-spacing: 1.2px;
                        text-transform: uppercase;
                    }

                    .duration-row {
                        margin-top: 8px;
                    }

                    .duration-row .detail-item {
                        gap: 12px;
                    }

                    .detail-item {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                        font-size: 18px;
                        color: #1f2937;
                        flex-wrap: wrap;
                        text-align: center;
                    }

                    .dashed-field {
                        display: inline-block;
                        min-width: 150px;
                        border-bottom: 2px dotted #a3a3a3;
                        padding: 0 8px 4px;
                        letter-spacing: 0.5px;
                        color: #111827;
                        text-align: center;
                        max-width: 100%;
                    }

                    .dashed-field.roll-field {
                        min-width: 120px;
                        font-size: 20px;
                        letter-spacing: 0.8px;
                    }

                    .dashed-field.passport-field {
                        min-width: 135px;
                    }

                    .duration-field {
                        min-width: 140px;
                    }

                    .issue-line {
                        margin-top: 6px;
                        font-size: 15px;
                        color: #1f2937;
                    }

                    .info-label,
                    .detail-label-text {
                        font-weight: 600;
                        letter-spacing: 0.4px;
                    }

                    /* Footer section */
                    .certificate-footer {
                        display: flex;
                        justify-content: space-between;
                        width: 100%;
                        align-items: end;
                        padding: 0 30px 10px;
                        box-sizing: border-box;
                    }

                    .signature-block {
                        text-align: center;
                    }

                    .signature-line {
                        width: 175px;
                        height: 38px;
                        border-bottom: 2px solid #333;
                        margin: 0 auto 5px;
                    }

                    .signature-title {
                        font-size: 14px;
                        color: #111;
                        font-weight: 700;
                        text-transform: uppercase;
                        letter-spacing: 0.8px;
                    }

                    .qr-code-container {
                        width: 120px;
                        height: 120px;
                        border: 2px solid #e8dcc4;
                        padding: 6px;
                        background: #fff;
                        border-radius: 6px;
                        box-sizing: border-box;
                        margin: 0 auto;
                    }

                    .qr-code-container img {
                        width: 100%;
                        height: 100%;
                        display: block;
                    }

                    .qr-label {
                        display: none;
                    }

                    /* Print styles */
                    @media print {
                        :root {
                            --page-padding: 6mm;
                        }

                        * {
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }

                        body {
                            background: white;
                            padding: 0;
                            margin: 0;
                            width: 297mm;
                            height: 210mm;
                            overflow: hidden;
                        }

                        .certificate-container {
                            box-shadow: none;
                            width: 297mm;
                            height: 210mm;
                            max-width: 297mm;
                            max-height: 210mm;
                            aspect-ratio: 297 / 210;
                            padding: 5mm;
                            margin: 0;
                            page-break-after: avoid;
                            page-break-before: avoid;
                            page-break-inside: avoid;
                            border-radius: 0;
                            overflow: hidden;
                        }

                        .certificate-container::before {
                            background: rgba(255, 255, 255, 0.85);
                        }

                        .certificate-watermark {
                            inset: 20mm 30mm;
                            opacity: 0.20;
                        }

                        .certificate-word {
                            font-family: 'Great Vibes', 'Cormorant Garamond', serif;
                            font-size: 120px;
                        }

                        .certificate-border::before {
                            inset: -3px;
                        }

                        .certificate-border::after {
                            inset: 4px;
                        }

                        .certificate-content {
                            overflow: hidden;
                        }

                        .certificate-header {
                            margin-bottom: -8px;
                        }

                        .header-photos {
                            gap: 20px;
                        }

                        .logo-container,
                        .student-photo-container {
                            width: 130px;
                            height: 150px;
                        }

                        .company-name {
                            font-size: var(--company-name-font-size-print);
                            margin-bottom: 8px;
                        }

                        .company-address {
                            font-size: 20px;
                        }

                        .certificate-title {
                            margin: -22px 0 10px;
                        }

                        .certificate-word {
                            font-size: 88px;
                            margin-bottom: 2px;
                        }

                        .certificate-subtitle {
                            font-size: 88px;
                        }

                        .certificate-body {
                            padding: 0 25px 3px;
                            gap: 10px;
                        }

                        .intro-text {
                            font-size: 17px;
                        }

                        .student-name {
                            font-size: 42px;
                            margin: 6px auto;
                        }

                        .family-line {
                            font-size: 17px;
                        }

                        .info-row {
                            font-size: 16px;
                        }

                        .statement-text {
                            font-size: 17px;
                            line-height: 1.6;
                        }

                        .identity-row {
                            gap: 30px;
                        }

                        .detail-item {
                            font-size: 16px;
                        }

                        .dashed-field {
                            min-width: 180px;
                        }

                        .certificate-footer {
                            /*padding: 0 20px 3px;*/
                            gap: 20px;
                        }

                        .signature-line {
                            width: 160px;
                            height: 32px;
                            margin: 0 auto 4px;
                        }

                        .signature-title {
                            font-size: 13px;
                        }

                        .qr-code-container {
                            width: 100px;
                            height: 100px;
                        }
                    }

                    @page {
                        size: A4 landscape;
                        margin: 0;
                    }
                </style>
            </head>
            <body>
                <div class="certificate-container">
                    <div class="certificate-watermark">
                        <img src="${companyLogo}" alt="Certificate Watermark" onerror="this.style.display='none'">
                    </div>
                    <div class="certificate-border"></div>

                    <div class="certificate-content">
                        <!-- Header -->
                        <div class="certificate-header">
                            <div class="header-center">
                                <div class="company-name">${instituteName}</div>
                                <div class="company-address">
                                    ${escapeHtml(INSTITUTE.address)}${INSTITUTE.address && INSTITUTE.contact ? '<br>' : ''}
                                    ${escapeHtml(INSTITUTE.contact)}
                                </div>
                            </div>

                            <div class="header-photos">
                                <div class="logo-container">
                                    <img src="${companyLogo}" alt="Company Logo" onerror="this.style.display='none'">
                                </div>

                                <div class="student-photo-container">
                                    <img src="${studentPhoto}" alt="Student Photo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'120\\' height=\\'140\\' viewBox=\\'0 0 120 140\\'%3E%3Crect fill=\\'%23f0f0f0\\' width=\\'120\\' height=\\'140\\'/%3E%3Ctext x=\\'50%25\\' y=\\'50%25\\' dominant-baseline=\\'middle\\' text-anchor=\\'middle\\' fill=\\'%23999\\' font-size=\\'40\\' font-family=\\'Arial\\'%3E${escapeHtml((student.name || 'S').charAt(0))}%3C/text%3E%3C/svg%3E'">
                                </div>
                            </div>
                        </div>

                        <!-- Certificate Title -->
                        <div class="certificate-title">
                            <span class="certificate-word">Certificate</span>
                            <span class="certificate-subtitle">of Completion</span>
                        </div>

                        <!-- Body -->
                        <div class="certificate-body">
                            <div class="intro-text">
                                This is to certify that
                            </div>

                            <div class="student-name">${escapeHtml(student.name || '_______________________')}</div>

                            <p class="family-line">
                                Son of <strong>${fatherDisplay}</strong> &amp; <strong>${motherDisplay}</strong>
                            </p>

                            <div class="info-row">
                                <div class="info-item">
                                    <span class="info-label">Date of Birth:</span>
                                    <span class="dashed-field">${dobDisplay}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Roll No:</span>
                                    <span class="dashed-field roll-field">${rollDisplay}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Passport No:</span>
                                    <span class="dashed-field passport-field">${passportDisplay}</span>
                                </div>
                            </div>

                            <p class="statement-text">
                                has successfully completed the ${programType} program conducted by ${instituteName} and demonstrated the required professional competencies.
                            </p>

                            <div class="identity-row">
                                <div class="detail-item course-detail">
                                    <span class="detail-label-text">Course:</span>
                                    <span class="course-template-text">${courseTemplateText}</span>
                                    <span class="dashed-field">${courseDisplay}</span>
                                </div>
                            </div>

                            <div class="identity-row duration-row">
                                <div class="detail-item">
                                    <span class="detail-label-text">Duration:</span>
                                    <span class="dashed-field duration-field"></span>
                                    <span class="detail-label-text">To</span>
                                    <span class="dashed-field duration-field"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="certificate-footer">
                            <div class="signature-block">
                                <div class="signature-line"></div>
                                <div class="signature-title">MANAGING DIRECTOR</div>
                            </div>

                            <div style="text-align: center;">
                                <div class="qr-code-container"${qrCodeUrl ? '' : ' style="visibility:hidden"'}>
                                    ${qrCodeUrl ? `<img src="${qrCodeUrl}" alt="Certificate QR Code">` : ''}
                                </div>
                            </div>

                            <div class="signature-block">
                                <div class="signature-line"></div>
                                <div class="signature-title">CHAIRMAN</div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    (function() {
                        const waitForAssets = () => {
                            const images = Array.from(document.images || []);
                            const imagePromises = images.map(img => {
                                if (img.complete && img.naturalWidth !== 0) {
                                    return Promise.resolve();
                                }
                                return new Promise(resolve => {
                                    const done = () => {
                                        img.removeEventListener('load', done);
                                        img.removeEventListener('error', done);
                                        resolve();
                                    };
                                    img.addEventListener('load', done, { once: true });
                                    img.addEventListener('error', done, { once: true });
                                });
                            });
                            const fontPromise = document.fonts ? document.fonts.ready.catch(() => undefined) : Promise.resolve();
                            const delay = new Promise(resolve => setTimeout(resolve, 200));
                            return Promise.all([...imagePromises, fontPromise, delay]).catch(() => undefined);
                        };

                        const triggerPrint = () => {
                            waitForAssets().finally(() => {
                                setTimeout(() => {
                                    try {
                                        window.focus();
                                        window.print();
                                    } catch (error) {
                                        console.error('Unable to open print dialog', error);
                                    }
                                }, 150);
                            });
                        };

                        if (document.readyState === 'complete') {
                            triggerPrint();
                        } else {
                            window.addEventListener('load', triggerPrint, { once: true });
                        }
                    })();
                </script>
            </body>
            </html>
        `;
    }

    function open(entry, student) {
        var win = window.open('', '_blank');
        if (!win) {
            if (window.Toast) Toast.error('Pop-up blocked. Please allow pop-ups to print certificates.');
            return;
        }
        win.document.write(buildCertificateHtml(entry, student));
        win.document.close();
        win.focus();
    }

    window.CertificatePrint = {
        build: buildCertificateHtml,
        open: open,
        // info = { name, address, contact, logo, verifyUrl } — empty values are ignored
        setInstitute: function (info) {
            Object.keys(INSTITUTE).forEach(function (k) { if (info && info[k]) INSTITUTE[k] = String(info[k]); });
        }
    };
})(window);
