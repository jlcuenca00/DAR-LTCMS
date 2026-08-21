<style>
    /* Reference layout: supplied DAR Land Transfer Clearance, folio 8.5 x 13 in.
       This layer intentionally overrides the older Form 5 presentation without
       introducing external fonts, scripts, or additional runtime dependencies. */

    :root {
        --ltc-font: Arial, Helvetica, sans-serif;
    }

    @page {
        size: 8.5in 13in;
        margin: 0;
    }

    .ltc-page {
        width: 816px !important;
        min-height: 1248px !important;
        margin: 14px auto 28px !important;
        padding: 25px 46px 22px !important;
        box-sizing: border-box !important;
        overflow: visible !important;
        font-size: 11.5px !important;
        line-height: 1.23 !important;
    }

    .top-form-no {
        display: none !important;
    }

    .official-header {
        display: grid !important;
        grid-template-columns: 184px minmax(0, 1fr) !important;
        gap: 12px !important;
        align-items: center !important;
        padding: 0 0 7px !important;
        border-bottom: 3px solid #111 !important;
    }

    .logos {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        min-width: 0 !important;
        white-space: nowrap !important;
    }

    .logo-img {
        display: block !important;
        width: auto !important;
        height: 76px !important;
        max-width: 82px !important;
        object-fit: contain !important;
    }

    .agency-lines {
        min-width: 0 !important;
        padding-top: 2px !important;
    }

    .agency-lines .republic {
        margin: 0 0 2px !important;
        font-size: 15px !important;
        line-height: 1 !important;
        letter-spacing: .01em !important;
    }

    .agency-lines .department {
        margin: 0 !important;
        font-size: 22px !important;
        line-height: 1.02 !important;
        font-weight: 800 !important;
        letter-spacing: 0 !important;
        white-space: nowrap !important;
    }

    .agency-lines .tagline {
        margin-top: 5px !important;
        color: #75a958 !important;
        font-size: 15px !important;
        line-height: 1.05 !important;
        font-weight: 700 !important;
    }

    .ltc-number-row {
        margin: 10px 32px 0 0 !important;
        display: flex !important;
        justify-content: flex-end !important;
    }

    .ltc-number {
        min-height: 29px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
        box-sizing: border-box !important;
        padding: 2px 8px !important;
        border: 3px solid #6fa7d8 !important;
        background: #fff !important;
        font-size: 10.5px !important;
        font-weight: 700 !important;
    }

    .title {
        margin: 0 0 6px !important;
        text-align: center !important;
    }

    .title h1 {
        margin: 0 !important;
        color: #111 !important;
        font-size: 24px !important;
        line-height: 1 !important;
        font-weight: 800 !important;
        letter-spacing: 0 !important;
    }

    .title p {
        margin: 3px 0 0 !important;
        color: #222 !important;
        font-size: 12px !important;
        line-height: 1.1 !important;
    }

    .intro {
        width: 702px !important;
        margin: 0 auto 13px !important;
        color: #242424 !important;
        font-size: 11.5px !important;
        line-height: 1.42 !important;
        font-style: normal !important;
    }

    .detail-table {
        width: 665px !important;
        margin: 0 auto !important;
        table-layout: fixed !important;
    }

    .detail-table td {
        padding: 7px 3px !important;
        vertical-align: top !important;
    }

    .detail-label {
        width: 120px !important;
        padding-right: 10px !important;
        color: #262626 !important;
        text-align: right !important;
        white-space: nowrap !important;
        font-style: normal !important;
    }

    .detail-value {
        color: #111 !important;
        font-weight: 700 !important;
        text-decoration: underline !important;
        text-underline-offset: 2px !important;
        overflow-wrap: anywhere !important;
    }

    .decision-line {
        width: 665px !important;
        margin: 45px auto 37px !important;
        padding-left: 78px !important;
        box-sizing: border-box !important;
        display: flex !important;
        align-items: center !important;
        gap: 30px !important;
    }

    .decision-line .prefix {
        color: #2c2c2c !important;
    }

    .decision-box {
        min-width: 96px !important;
        padding: 9px 16px !important;
        border: 3px solid #111 !important;
        color: #111 !important;
        text-align: center !important;
        font-weight: 800 !important;
        letter-spacing: .015em !important;
    }

    .basis {
        width: 702px !important;
        margin: 0 auto !important;
        color: #222 !important;
        font-size: 11px !important;
        line-height: 1.28 !important;
        text-align: justify !important;
    }

    .basis p {
        margin: 0 0 3px !important;
    }

    .issued-line {
        width: 702px !important;
        margin: 35px auto 8px !important;
        color: #222 !important;
        font-size: 11px !important;
    }

    .issued-line .blank {
        min-width: 158px !important;
        padding: 0 6px 1px !important;
        color: #111 !important;
        text-align: center !important;
        font-weight: 600 !important;
    }

    .lower-area {
        width: 702px !important;
        margin: 0 auto !important;
        display: grid !important;
        grid-template-columns: 190px minmax(0, 1fr) !important;
        gap: 44px !important;
        align-items: end !important;
    }

    .payment-table {
        width: 142px !important;
        margin: 0 !important;
        border: 1px solid #111 !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        color: #222 !important;
        font-size: 9.5px !important;
    }

    .payment-table td {
        border: 0 !important;
        padding: 1px 5px !important;
    }

    .payment-table .head {
        padding-top: 4px !important;
        padding-bottom: 2px !important;
        font-style: normal !important;
        font-weight: 700 !important;
        text-decoration: underline !important;
    }

    .signature {
        padding-bottom: 5px !important;
        color: #111 !important;
        text-align: center !important;
    }

    .signatory {
        font-size: 12px !important;
        font-weight: 800 !important;
    }

    .signatory-title {
        margin-top: 4px !important;
        font-size: 11px !important;
    }

    .warning-box {
        width: 702px !important;
        margin: 2px auto 0 !important;
        padding: 5px 7px 6px !important;
        box-sizing: border-box !important;
        border: 1px solid #111 !important;
        color: #222 !important;
        font-size: 10px !important;
        line-height: 1.25 !important;
    }

    .warning-box strong {
        color: #111 !important;
        font-weight: 800 !important;
    }

    .green-bars {
        width: 682px !important;
        margin: 2px auto 0 !important;
        display: grid !important;
        grid-template-columns: 305px minmax(0, 1fr) !important;
        gap: 74px !important;
        align-items: start !important;
        border-bottom: 0 !important;
    }

    .green-bar {
        min-height: 33px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-sizing: border-box !important;
        background: #79b957 !important;
        color: #fff !important;
        padding: 4px 7px !important;
        font-size: 10.5px !important;
        line-height: 1.18 !important;
        font-weight: 700 !important;
        text-align: center !important;
        text-transform: uppercase !important;
    }

    .footer {
        position: absolute !important;
        left: 46px !important;
        right: 46px !important;
        bottom: 22px !important;
        width: auto !important;
        margin: 0 !important;
        padding: 6px 2px 0 !important;
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) 175px !important;
        gap: 24px !important;
        border-top: 3px solid #111 !important;
        color: #111 !important;
        font-size: 10.5px !important;
        line-height: 1.28 !important;
    }

    .print-toolbar {
        font-family: Arial, Helvetica, sans-serif !important;
    }

    @media print {
        html,
        body {
            width: 8.5in !important;
            height: 13in !important;
            background: #fff !important;
        }

        .print-toolbar {
            display: none !important;
        }

        .ltc-page {
            width: 8.5in !important;
            min-height: 13in !important;
            margin: 0 !important;
            padding: 25px 46px 22px !important;
            box-shadow: none !important;
            page-break-after: avoid !important;
        }
    }
</style>
