<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    public $timestamps = false;
    use HasFactory;

    public function playerIndex()
    {
        return (
        Player::query()->
        select(['id', 'name'])->
        get());
    }

    public function playerUpdate($id,$name,$hp,$mp,$money)
    {
            Player::query()->
            where('id',$id)->
            update(
                [
                    'name' => $name,
                    'hp' => $hp,
                    'mp' => $mp,
                    'money' => $money
                ]
            );
    }

    public function playerCreate($name,$hp,$mp,$money)
    {
       
            return(
                Player::query()->
                insertGetId(
                [
                    'name'=>$name,
                    'hp' => $hp,
                    'mp' => $mp,
                    'money' => $money
                ]
            ));
    }

    public function playerDestroy($id)
    {
                Player::query()->
                where('id',$id)->
                delete();
    }
}
