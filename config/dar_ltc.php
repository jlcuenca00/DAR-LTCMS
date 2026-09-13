<?php

return [
    // Citizen's Charter / DAR A.O. No. 4, s. 2021 filing fee.
    // Keep this configurable so a future office-approved fee change does not require workflow code changes.
    'filing_fee' => (float) env('DAR_LTC_FILING_FEE', 2000),

    // The system validates document age only as an administrative aid.
    // Final legal acceptance remains with authorized DAR personnel.
    'document_validity_months' => 6,
];
