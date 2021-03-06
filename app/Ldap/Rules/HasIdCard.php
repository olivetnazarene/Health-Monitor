<?php

namespace App\Ldap\Rules;

use LdapRecord\Laravel\Auth\Rule;

class HasIdCard extends Rule
{
    /**
     * Check if the rule passes validation.
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->user->getFirstAttribute('samAccountName') === getenv("ADMIN_ACCOUNT")  
        || !empty($this->user->getFirstAttribute('extensionAttribute13'));
    }
}
