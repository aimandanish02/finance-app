<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [

            // ── Personal Reliefs ──────────────────────────────────────────
            [
                'name'              => 'Self Relief',
                'code'              => 'PERSONAL',
                'color'             => '#6366f1',
                'is_tax_deductible' => true,
                'deduction_type'    => 'PERSONAL',
                'annual_limit'      => 9000.00,
                'description'       => 'Basic individual relief for all resident taxpayers.',
            ],
            [
                'name'              => 'Spouse Relief',
                'code'              => 'SPOUSE',
                'color'             => '#8b5cf6',
                'is_tax_deductible' => true,
                'deduction_type'    => 'SPOUSE',
                'annual_limit'      => 4000.00,
                'description'       => 'Relief for spouse under joint assessment.',
            ],
            [
                'name'              => 'Alimony to Former Wife',
                'code'              => 'ALIMONY',
                'color'             => '#a78bfa',
                'is_tax_deductible' => true,
                'deduction_type'    => 'ALIMONY',
                'annual_limit'      => 4000.00,
                'description'       => 'Alimony payments made to former wife.',
            ],

            // ── Child Reliefs ─────────────────────────────────────────────
            [
                'name'              => 'Child Below 18',
                'code'              => 'CHILD_U18',
                'color'             => '#f59e0b',
                'is_tax_deductible' => true,
                'deduction_type'    => 'CHILD',
                'annual_limit'      => 2000.00,
                'description'       => 'Relief for each child below 18 years of age.',
            ],
            [
                'name'              => 'Child 18+ (Pre-Degree / Matriculation)',
                'code'              => 'CHILD_PREDEGREE',
                'color'             => '#fbbf24',
                'is_tax_deductible' => true,
                'deduction_type'    => 'CHILD',
                'annual_limit'      => 2000.00,
                'description'       => 'Child over 18 in matriculation, pre-degree, or A-Level.',
            ],
            [
                'name'              => 'Child 18+ (Tertiary / Higher Education)',
                'code'              => 'CHILD_DEGREE',
                'color'             => '#f97316',
                'is_tax_deductible' => true,
                'deduction_type'    => 'CHILD',
                'annual_limit'      => 8000.00,
                'description'       => 'Child over 18 in diploma or higher at local/overseas institution, or serving articles/indentures.',
            ],

            // ── Disabled Reliefs ──────────────────────────────────────────
            [
                'name'              => 'Disabled Self (Additional)',
                'code'              => 'DISABLED_SELF',
                'color'             => '#ef4444',
                'is_tax_deductible' => true,
                'deduction_type'    => 'DISABLED_SELF',
                'annual_limit'      => 7000.00,
                'description'       => 'Additional relief for a disabled individual taxpayer.',
            ],
            [
                'name'              => 'Disabled Spouse (Additional)',
                'code'              => 'DISABLED_SPOUSE',
                'color'             => '#f87171',
                'is_tax_deductible' => true,
                'deduction_type'    => 'DISABLED_SPOUSE',
                'annual_limit'      => 6000.00,
                'description'       => 'Additional relief for a disabled spouse.',
            ],
            [
                'name'              => 'Disabled Child',
                'code'              => 'DISABLED_CHILD',
                'color'             => '#fca5a5',
                'is_tax_deductible' => true,
                'deduction_type'    => 'DISABLED_CHILD',
                'annual_limit'      => 8000.00,
                'description'       => 'Relief for each physically or mentally handicapped child.',
            ],
            [
                'name'              => 'Disabled Child (18+ in Higher Education)',
                'code'              => 'DISABLED_CHILD_EDU',
                'color'             => '#fecaca',
                'is_tax_deductible' => true,
                'deduction_type'    => 'DISABLED_CHILD',
                'annual_limit'      => 8000.00,
                'description'       => 'Additional relief for disabled child over 18 receiving higher education or serving articles/indentures.',
            ],
            [
                'name'              => 'Disability Supporting Equipment',
                'code'              => 'DISABILITY_EQUIPMENT',
                'color'             => '#dc2626',
                'is_tax_deductible' => true,
                'deduction_type'    => 'DISABILITY_EQUIPMENT',
                'annual_limit'      => 6000.00,
                'description'       => 'Purchase of disability-supporting equipment for individual, spouse, child, or parent (e.g. wheelchair, hearing aid, crutches).',
            ],

            // ── Medical ───────────────────────────────────────────────────
            [
                'name'              => 'Medical — Self / Spouse / Child',
                'code'              => 'MEDICAL_SELF',
                'color'             => '#10b981',
                'is_tax_deductible' => true,
                'deduction_type'    => 'MEDICAL_SELF',
                'annual_limit'      => 10000.00,
                'description'       => 'Serious disease, fertility treatment, vaccination (≤RM1,000), dental examination/treatment (≤RM1,000), medical examination/self-testing device/mental health consultation (≤RM1,000), and learning disability early intervention/rehabilitation (≤RM6,000).',
            ],
            [
                'name'              => 'Medical — Parents',
                'code'              => 'MEDICAL_PARENT',
                'color'             => '#059669',
                'is_tax_deductible' => true,
                'deduction_type'    => 'MEDICAL_PARENT',
                'annual_limit'      => 8000.00,
                'description'       => 'Medical treatment, dental treatment, full medical examination including vaccination (≤RM1,000), special needs or carer expenses for parents.',
            ],

            // ── Insurance & Savings ───────────────────────────────────────
            [
                'name'              => 'EPF Contribution',
                'code'              => 'EPF',
                'color'             => '#0ea5e9',
                'is_tax_deductible' => true,
                'deduction_type'    => 'EPF',
                'annual_limit'      => 4000.00,
                'description'       => 'Mandatory and additional voluntary EPF contributions by individual.',
            ],
            [
                'name'              => 'Life Insurance / Takaful',
                'code'              => 'LIFE_INSURANCE',
                'color'             => '#38bdf8',
                'is_tax_deductible' => true,
                'deduction_type'    => 'LIFE_INSURANCE',
                'annual_limit'      => 3000.00,
                'description'       => 'Life insurance premiums for individual and/or spouse, and additional voluntary EPF contributions.',
            ],
            [
                'name'              => 'Education & Medical Insurance',
                'code'              => 'EDU_INSURANCE',
                'color'             => '#7dd3fc',
                'is_tax_deductible' => true,
                'deduction_type'    => 'EDUCATION_INSURANCE',
                'annual_limit'      => 4000.00,
                'description'       => 'Premiums for insurance on education or medical benefits for individual, spouse, or child.',
            ],
            [
                'name'              => 'Private Retirement Scheme (PRS)',
                'code'              => 'PRS',
                'color'             => '#0284c7',
                'is_tax_deductible' => true,
                'deduction_type'    => 'PRS',
                'annual_limit'      => 3000.00,
                'description'       => 'Contributions to Private Retirement Scheme and Deferred Annuity Scheme (YA2012–YA2030).',
            ],
            [
                'name'              => 'SOCSO / EIS Contribution',
                'code'              => 'SOCSO',
                'color'             => '#0369a1',
                'is_tax_deductible' => true,
                'deduction_type'    => 'SOCSO',
                'annual_limit'      => 350.00,
                'description'       => "Employee's contribution to SOCSO and Employment Insurance System (EIS).",
            ],
            [
                'name'              => 'SSPN Deposit',
                'code'              => 'SSPN',
                'color'             => '#1d4ed8',
                'is_tax_deductible' => true,
                'deduction_type'    => 'SSPN',
                'annual_limit'      => 8000.00,
                'description'       => 'Deposit under National Education Savings Scheme (Skim Simpanan Pendidikan Nasional), claimable by either parent (YA2025–YA2027).',
            ],

            // ── Education ─────────────────────────────────────────────────
            [
                'name'              => 'Education Fees (Self)',
                'code'              => 'EDU_SELF',
                'color'             => '#7c3aed',
                'is_tax_deductible' => true,
                'deduction_type'    => 'EDUCATION_SELF',
                'annual_limit'      => 7000.00,
                'description'       => 'Course fees in Malaysia up to tertiary level (law, accountancy, Islamic financing, technical, vocational, industrial, scientific, technological skills), upskilling/self-enhancement courses recognised by DG of Skills Development (≤RM2,000, YA2022–YA2026), or Masters/Doctorate degree for any skill or qualification.',
            ],

            // ── Lifestyle ─────────────────────────────────────────────────
            [
                'name'              => 'Lifestyle Relief',
                'code'              => 'LIFESTYLE',
                'color'             => '#db2777',
                'is_tax_deductible' => true,
                'deduction_type'    => 'LIFESTYLE',
                'annual_limit'      => 2500.00,
                'description'       => 'Purchase/subscription of books, journals, magazines, newspapers (including e-newspapers); personal computer, smartphone, or tablet; internet subscription; upskilling/self-enhancement courses.',
            ],
            [
                'name'              => 'Lifestyle — Sports',
                'code'              => 'LIFESTYLE_SPORTS',
                'color'             => '#be185d',
                'is_tax_deductible' => true,
                'deduction_type'    => 'LIFESTYLE_SPORTS',
                'annual_limit'      => 1000.00,
                'description'       => 'Sports equipment, entry/rental fees for sports facilities, registration fees for sports competitions, gym membership, and sports training fees.',
            ],
            [
                'name'              => 'Breastfeeding Equipment',
                'code'              => 'BREASTFEEDING',
                'color'             => '#f472b6',
                'is_tax_deductible' => true,
                'deduction_type'    => 'BREASTFEEDING',
                'annual_limit'      => 1000.00,
                'description'       => 'Purchase of breastfeeding equipment once every two years (for women taxpayers only).',
            ],
            [
                'name'              => 'Childcare & Kindergarten Fees',
                'code'              => 'CHILDCARE',
                'color'             => '#fb7185',
                'is_tax_deductible' => true,
                'deduction_type'    => 'CHILDCARE',
                'annual_limit'      => 3000.00,
                'description'       => 'Fees paid to registered childcare centres and kindergartens for child/children below 6 years old.',
            ],
            [
                'name'              => 'EV Charging / Food Waste Composter',
                'code'              => 'EV_CHARGING',
                'color'             => '#22c55e',
                'is_tax_deductible' => true,
                'deduction_type'    => 'EV_CHARGING',
                'annual_limit'      => 2500.00,
                'description'       => 'Costs for EV charging facilities (installation, rental, hire-purchase, subscription) YA2023–YA2027, or purchase of food waste composting machine for household use from YA2025–YA2027.',
            ],

            // ── Housing ───────────────────────────────────────────────────
            [
                'name'              => 'Housing Loan Interest (≤RM500k)',
                'code'              => 'HOUSING_LOAN_500',
                'color'             => '#16a34a',
                'is_tax_deductible' => true,
                'deduction_type'    => 'HOUSING_LOAN',
                'annual_limit'      => 7000.00,
                'description'       => 'Home loan interest for first 3 consecutive years of assessment on residential property valued at RM500,000 and below (SPA executed 1 Jan 2025–31 Dec 2027).',
            ],
            [
                'name'              => 'Housing Loan Interest (RM500k–RM750k)',
                'code'              => 'HOUSING_LOAN_750',
                'color'             => '#15803d',
                'is_tax_deductible' => true,
                'deduction_type'    => 'HOUSING_LOAN',
                'annual_limit'      => 5000.00,
                'description'       => 'Home loan interest for first 3 consecutive years of assessment on residential property valued between RM500,000 and RM750,000 (SPA executed 1 Jan 2025–31 Dec 2027).',
            ],

            // ── Zakat & Donations ─────────────────────────────────────────
            [
                'name'              => 'Zakat / Fitrah',
                'code'              => 'ZAKAT',
                'color'             => '#ca8a04',
                'is_tax_deductible' => true,
                'deduction_type'    => 'ZAKAT',
                'annual_limit'      => null,
                'description'       => 'Zakat and fitrah payments. Qualifies as a tax rebate (fully deducted against tax charged, no limit).',
            ],
            [
                'name'              => 'Donation to Approved Institution',
                'code'              => 'DONATION',
                'color'             => '#eab308',
                'is_tax_deductible' => true,
                'deduction_type'    => 'DONATION',
                'annual_limit'      => null,
                'description'       => 'Cash donations to LHDN-approved institutions or organisations. Deductible up to 10% of aggregate income.',
            ],

            // ── Employment Expenses ───────────────────────────────────────
            [
                'name'              => 'Employment Expenses',
                'code'              => 'EMPLOYMENT',
                'color'             => '#64748b',
                'is_tax_deductible' => true,
                'deduction_type'    => 'EMPLOYMENT',
                'annual_limit'      => null,
                'description'       => 'Expenses incurred wholly and exclusively in the performance of employment duties, including professional association subscriptions.',
            ],

            // ── Business Deductions ───────────────────────────────────────
            [
                'name'              => 'Business Expenses',
                'code'              => 'BUSINESS',
                'color'             => '#475569',
                'is_tax_deductible' => true,
                'deduction_type'    => 'BUSINESS',
                'annual_limit'      => null,
                'description'       => 'Business-related expenses: home office, vehicle, utilities, professional fees, meals and travel incurred wholly for business purposes.',
            ],

            // ── Non-Deductible ────────────────────────────────────────────
            [
                'name'              => 'Food & Dining',
                'code'              => 'FOOD',
                'color'             => '#f97316',
                'is_tax_deductible' => false,
                'deduction_type'    => 'NOT_DEDUCTIBLE',
                'annual_limit'      => null,
                'description'       => 'General food, dining, and groceries. Not tax deductible.',
            ],
            [
                'name'              => 'Transport & Fuel',
                'code'              => 'TRANSPORT',
                'color'             => '#f59e0b',
                'is_tax_deductible' => false,
                'deduction_type'    => 'NOT_DEDUCTIBLE',
                'annual_limit'      => null,
                'description'       => 'Personal transport, fuel, tolls, parking. Not deductible unless business-related.',
            ],
            [
                'name'              => 'Utilities',
                'code'              => 'UTILITIES',
                'color'             => '#3b82f6',
                'is_tax_deductible' => false,
                'deduction_type'    => 'NOT_DEDUCTIBLE',
                'annual_limit'      => null,
                'description'       => 'Personal electricity, water, and gas bills. Not tax deductible.',
            ],
            [
                'name'              => 'Shopping & Personal',
                'code'              => 'SHOPPING',
                'color'             => '#ec4899',
                'is_tax_deductible' => false,
                'deduction_type'    => 'NOT_DEDUCTIBLE',
                'annual_limit'      => null,
                'description'       => 'Clothing, personal care, electronics, and general shopping. Not tax deductible.',
            ],
            [
                'name'              => 'Entertainment & Leisure',
                'code'              => 'ENTERTAINMENT',
                'color'             => '#a855f7',
                'is_tax_deductible' => false,
                'deduction_type'    => 'NOT_DEDUCTIBLE',
                'annual_limit'      => null,
                'description'       => 'Movies, travel, holidays, streaming subscriptions. Not tax deductible.',
            ],
            [
                'name'              => 'Housing & Rent',
                'code'              => 'HOUSING',
                'color'             => '#14b8a6',
                'is_tax_deductible' => false,
                'deduction_type'    => 'NOT_DEDUCTIBLE',
                'annual_limit'      => null,
                'description'       => 'Monthly rent or housing costs not qualifying for loan interest relief.',
            ],
            [
                'name'              => 'Other / Miscellaneous',
                'code'              => 'OTHER',
                'color'             => '#94a3b8',
                'is_tax_deductible' => false,
                'deduction_type'    => 'NOT_DEDUCTIBLE',
                'annual_limit'      => null,
                'description'       => 'General expenses that do not fall under any specific tax deduction category.',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['code' => $category['code']],
                array_merge($category, ['is_active' => true])
            );
        }
    }
}