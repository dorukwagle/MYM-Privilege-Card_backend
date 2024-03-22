<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MakeAnnouncements implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userType;
    protected $title;
    protected $body;
    /**
     * Create a new job instance.
     */
    public function __construct($title, $body, $userType)
    {
        $this->userType = $userType;
        $this->title = $title;
        $this->body = $body;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        
    }
}
