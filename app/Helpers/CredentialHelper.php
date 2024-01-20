<?php

namespace App\Helpers;

use App\Mail\RegistrationMail;
use Illuminate\Support\Facades\Mail;

class CredentialHelper
{
    private $email;
    private $password;
    private $sampleString = "12345678~@#$%&?=adgjmpsvZBFNQRXB";
    /**
     * Create a new job instance.
     */

    public function __construct($email)
    {
        $this->email = $email;

        $tmpPass = '';

        for($i = 1; $i <= 4; $i++) {
            for ($j = 1; $j <= 2; $j++) {
                $min = ($i - 1) * 8;
                $char = '';
                while (str_contains($tmpPass, ($char = $this->getCharacter(random_int($min, $min + 7)))));
                $tmpPass .= $char;
            }
        }
        $this->password = $tmpPass;
    }

    private function getCharacter($index) {
        return $this->sampleString[$index];
    }

   public function sendCredentials() {
        Mail::to($this->email)->send(new RegistrationMail($this->email, $this->password));
   }

   public function getPassword() {
        return $this->password;
   }
}
