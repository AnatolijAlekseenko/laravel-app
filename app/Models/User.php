<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'Users';

    protected $primaryKey = 'Id';

    public $timestamps = true;

    const CREATED_AT = 'CreatedAt';
    const UPDATED_AT = 'UpdatedAt';

    protected $fillable = [
        'Login',
        'Password',
        'Role',
        'IsActive',
        'DepartmentCode',
    ];

    protected $hidden = [
        'Password',
    ];

    public function getAuthPassword()
    {
        return $this->Password;
    }

    public function getAuthPasswordName()
    {
        return 'Password';
    }

    /**
     * Получить коды всех подразделений пользователя.
     */
    public function assignedDepartmentCodes()
    {
        return DB::table('UserDepartments')
            ->where('UserId', $this->Id)
            ->orderBy('DepartmentCode')
            ->pluck('DepartmentCode');
    }
}