<?php

namespace ItkDev\TidyFeedback\Model;

enum ItemCategory: string
{
    case BUG = 'bug';
    case MISSING_FEATURE = 'missing_feature';
}
