<?php

namespace Tests\Concerns;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\EmailValidation;
use Egulias\EmailValidator\Validation\RFCValidation;

/**
 * Валидация регистрации использует правило email:rfc,dns, которое резолвит MX
 * через сеть. В тестах это делает прогон недетерминированным (и падает без
 * интернета), поэтому подменяем валидатор на RFC-only: синтаксис адреса
 * по-прежнему проверяется, DNS — нет.
 */
trait DisablesDnsEmailValidation
{
    protected function disableDnsEmailValidation(): void
    {
        $this->app->bind(EmailValidator::class, fn (): EmailValidator => new class extends EmailValidator
        {
            public function isValid(string $email, EmailValidation $emailValidation): bool
            {
                return (new EmailValidator)->isValid($email, new RFCValidation);
            }
        });
    }
}
