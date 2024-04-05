<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    public $timestamps = false;
    use HasFactory;

    public function index()
    {
        return (
        Player::query()->
        select(['id', 'name'])->
        get());

        //echo 'a';
    }

    public function update_m($id,$name,$hp,$mp,$money)
    {
        //↓のupdateと関数名が同じだったためエラーがでていた
        try{
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

            echo 'success!';


        }catch(PDOException $e){
            echo 'error!';
        }
    }

    public function create($name,$hp,$mp,$money)
    {
        //
        try{
            $newid = Player::query()->
            insertGetId(
                [
                    'name'=>$name,
                    'hp' => $hp,
                    'mp' => $mp,
                    'money' => $money
                ]
            );

            echo'new id:'.$newid;

        }
        catch(PDOException $e)
            {
                echo'error!';
            }

    }

    public function destroy_m($id)
    {
        //destoryという関数名だと動かなかったため変更

        //idが数字か判定
        //if(is_int($id)){

            try{
                Player::query()->
                where('id',$id)->
                delete();
                echo 'success!';
            } 
            catch(PDOException $e)
            {
                echo'error!';
            }
        //}
        //else
        //{
        //    echo 'error:idが整数でない';
        //}
    }
}
