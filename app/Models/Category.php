<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'code',
        'color',
        'is_tax_deductible',
        'is_active',
        'deduction_type',
        'annual_limit',
        'description',
    ];

    protected $casts = [
        'is_tax_deductible' => 'boolean',
        'is_active'         => 'boolean',
        'annual_limit'      => 'decimal:2',
    ];

    /**
     * All valid LHDN deduction types with human-readable labels.
     */
    public static function deductionTypes(): array
    {
        return [
            'PERSONAL'             => 'Self Relief',
            'SPOUSE'               => 'Spouse Relief',
            'ALIMONY'              => 'Alimony to Former Wife',
            'CHILD'                => 'Child Relief',
            'DISABLED_SELF'        => 'Disabled Self (Additional)',
            'DISABLED_SPOUSE'      => 'Disabled Spouse (Additional)',
            'DISABLED_CHILD'       => 'Disabled Child',
            'DISABILITY_EQUIPMENT' => 'Disability Supporting Equipment',
            'MEDICAL_SELF'         => 'Medical — Self / Spouse / Child',
            'MEDICAL_PARENT'       => 'Medical — Parents',
            'EPF'                  => 'EPF Contribution',
            'LIFE_INSURANCE'       => 'Life Insurance / Takaful',
            'EDUCATION_INSURANCE'  => 'Education & Medical Insurance',
            'PRS'                  => 'Private Retirement Scheme (PRS)',
            'SOCSO'                => 'SOCSO / EIS Contribution',
            'SSPN'                 => 'SSPN Deposit',
            'EDUCATION_SELF'       => 'Education Fees (Self)',
            'LIFESTYLE'            => 'Lifestyle Relief',
            'LIFESTYLE_SPORTS'     => 'Lifestyle — Sports',
            'BREASTFEEDING'        => 'Breastfeeding Equipment',
            'CHILDCARE'            => 'Childcare & Kindergarten Fees',
            'EV_CHARGING'          => 'EV Charging / Food Waste Composter',
            'HOUSING_LOAN'         => 'Housing Loan Interest',
            'ZAKAT'                => 'Zakat / Fitrah',
            'DONATION'             => 'Donation to Approved Institution',
            'EMPLOYMENT'           => 'Employment Expenses',
            'BUSINESS'             => 'Business Expenses',
            'NOT_DEDUCTIBLE'       => 'Not Tax Deductible',
        ];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}