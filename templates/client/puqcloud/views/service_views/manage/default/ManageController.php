<?php

use App\Traits\ServiceViewManageActions;

/**
 * Default ManageController for service_views.
 *
 * The four generic manage actions (SetLabel, TerminationRequest, CancelTerminationRequest,
 * SwitchBillingMode) live in the App\Traits\ServiceViewManageActions trait — any module can
 * `use` that trait in its own ManageController and add module-specific endpoints on top.
 */
class ManageController
{
    use ServiceViewManageActions;

    public function __construct() {}
}
