<?php

declare(strict_types=1);

namespace Lowseekai\ContentRiskFee;

use Flarum\Foundation\AbstractServiceProvider;
use Lowseekai\ContentRiskFee\Analyzer\RiskAnalyzer;
use Lowseekai\ContentRiskFee\Support\ContentRiskService;
use Lowseekai\ContentRiskFee\Support\RiskSettings;

class ContentRiskFeeServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(RiskSettings::class);
        $this->container->singleton(RiskAnalyzer::class);
        $this->container->singleton(ContentRiskService::class);
    }
}
