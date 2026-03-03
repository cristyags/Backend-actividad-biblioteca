<?php

namespace App\Providers;

use App\Models\Book;
use App\Models\Loan;
use App\Policies\BookPolicy;
use App\Policies\LoanPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Book::class => BookPolicy::class,
        Loan::class => LoanPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}