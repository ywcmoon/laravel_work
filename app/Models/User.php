<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'avatar',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    // // ✅ 如果需要处理头像URL，可以使用访问器（但名称不要叫avatar）：
    // public function getAvatarUrlAttribute()
    // {
    //     if ($this->avatar) {
    //         return asset('storage/' . $this->avatar);
    //     }
    //     return asset('images/default-avatar.png');
    // }

    public function messages()
    {
        return $this->hasMany(\App\Models\Message::class);
    }

  
}
