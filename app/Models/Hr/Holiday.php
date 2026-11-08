<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $table = 'hr_holidays';

    protected $fillable = [
        'name',
        'date',
        'end_date',
        'type',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get employees assigned to this holiday.
     * If no employees are assigned, it might be interpreted as applying to all.
     */
    public function employees()
    {
        return $this->belongsToMany(\App\Models\Hr\Employee::class, 'hr_employee_holiday', 'holiday_id', 'employee_id')->withTimestamps();
    }

    /**
     * Check if a specific date is a holiday
     * Also checks if date falls within date and end_date
     */
    public static function isHoliday($date, $employeeId = null)
    {
        return self::where(function ($query) use ($date) {
            $query->where(function ($q) use ($date) {
                // Single-day holiday (no end_date): must match exact date
                $q->whereNull('end_date')
                  ->whereDate('date', '=', $date);
            })->orWhere(function ($q) use ($date) {
                // Multi-day holiday: date must fall within the range
                $q->whereNotNull('end_date')
                  ->whereDate('date', '<=', $date)
                  ->whereDate('end_date', '>=', $date);
            });
        })->when($employeeId, function ($query) use ($employeeId) {
            $query->where(function($q) use ($employeeId) {
                $q->doesntHave('employees')
                  ->orWhereHas('employees', function($sq) use ($employeeId) {
                      $sq->where('hr_employees.id', $employeeId);
                  });
            });
        })->exists();
    }

    /**
     * Get holiday for a specific date
     */
    public static function getHoliday($date, $employeeId = null)
    {
        return self::with('employees')->where(function ($query) use ($date) {
            $query->where(function ($q) use ($date) {
                // Single-day holiday (no end_date): must match exact date
                $q->whereNull('end_date')
                  ->whereDate('date', '=', $date);
            })->orWhere(function ($q) use ($date) {
                // Multi-day holiday: date must fall within the range
                $q->whereNotNull('end_date')
                  ->whereDate('date', '<=', $date)
                  ->whereDate('end_date', '>=', $date);
            });
        })->when($employeeId, function ($query) use ($employeeId) {
            $query->where(function($q) use ($employeeId) {
                $q->doesntHave('employees')
                  ->orWhereHas('employees', function($sq) use ($employeeId) {
                      $sq->where('hr_employees.id', $employeeId);
                  });
            });
        })->first();
    }

    /**
     * Get all holidays for a month
     */
    public static function getMonthHolidays($year, $month)
    {
        return self::with('employees')->whereYear('date', $year)
                   ->whereMonth('date', $month)
                   ->orderBy('date')
                   ->get();
    }

    /**
     * Get all holidays for a year
     */
    public static function getYearHolidays($year)
    {
        return self::with('employees')->whereYear('date', $year)
                   ->orderBy('date')
                   ->get();
    }
}
