<?php

namespace App\Enums;

enum ExpenseStatus: string
{
    case Draft = 'draft';
    case ProgramReview = 'program_review';
    case FinanceReview = 'finance_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
