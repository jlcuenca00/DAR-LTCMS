@include('staff.clearances.show', [
    'application' => $application,
    'clearance' => $clearance,
    'returnRoute' => route('landowner.applications.index'),
    'returnLabel' => 'Back to My Applications',
])
