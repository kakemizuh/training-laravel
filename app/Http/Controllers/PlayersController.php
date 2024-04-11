<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlayerResource;
use App\Models\Player;
use App\Models\PlayerItem;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Exception;


class PlayersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $player = new Player();
        return new Response(
            $player->playerIndex()
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $player = new Player();

        try
        {
            $player->playerUpdate($id,$request->name,$request->hp,$request->mp,$request->money);
            return 'success';
        }
        catch(QueryException $e)
        {
            return 'error';
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $player = new Player();
        try
        {
            $player->playerDestroy($id);
            return'success';
        }
        catch(QueryException $e)
        {
            return'error';
        }

        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $player = new Player();
        try
        {
            $newid = $player->playerCreate($request->name,$request->hp,$request->mp,$request->money);
            return new Response(["id"=>$newid]);
        }
        catch(QueryException $e)
        {
            return'error';
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * idで指定したプレイヤーのアイテムを増加させる
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function addItem($id,Request $request)
    {
        $playerItem = new PlayerItem();

        try
        {
            if($playerItem->playerItemExists($id,$request->itemId))
            {
                $itemcount = $playerItem->playerItemGet($id,$request->itemId)["itemcount"];
                $itemcount = $request->count + $itemcount;
                $playerItem->playerItemUpdate($id,$request->itemId,$itemcount);
            }
            else
            {
                $playerItem->playerItemCreate($id,$request->itemId,$request->count);
                
                $itemcount=$request->count;
            }
            return new Response(["itemId"=>$request->itemId,"count"=>$itemcount]);
        }
        catch(QueryException $e)
        {
            return'error';
        }
    }
    
    /**
     * 指定したアイテムを使用し、効果によってプレイヤーのステータスを更新する
     * 
     * @param int id
     * @return \Illuminate\Http\Response
     */
    public function useItem($id,Request $request)
    {
        $player = new Player();
        $playerItem = new PlayerItem();
        $item = new Item();

        try
        {
            $playerdata=$player->playerGet($id);
            if($playerdata == null)
            {
                throw new Exception('no playerdata');
            }
            $hp=$playerdata["hp"];
            $mp=$playerdata["mp"];
            $itemdatas = $item->itemIndex();
            if($itemdatas[0] == null)
            {
                throw new Exception('no itemdata');
            }
            $playeritemdata = $playerItem->playerItemGet($id,$request->itemId);
            if($playeritemdata == null)
            {
                throw new Exception('error:400');
            }
            $itemcount = $playeritemdata["itemcount"];
            if($itemcount == 0)
            {
                throw new Exception('error:400');
            }
            else if($itemcount<$request->count)
            {
                throw new Exception('usecount exceeds playeritemcount');
            }
            else
            {
                foreach($itemdatas as $itemdata)
                {
                    if($itemdata->id == $request->itemId)
                    {
                        if($itemdata->item_type == 1)
                        {
                            $status = $hp;
                        }
                        else if($itemdata->item_type == 2)
                        {
                            $status = $mp;
                        }
                        else
                        {
                            throw new Exception('no item_type');
                        }
                        $itemValue = $itemdata->value;
                        break;
                    }
                }

                if($status<200)
                {
                    if($itemValue*$request->count+$status >= 200)
                    {
                        $useitemcount = 0;
                        for($i=0;$i<$request->count;$i++)
                        {
                            $useitemcount++;
                            if($itemValue*$useitemcount+$status >= 200)
                            {
                                break;
                            }
                        }
                        $itemcount-=$useitemcount;
                        $status=200;
                    }
                    else
                    {
                        $itemcount-=$request->count;
                        $status+=$itemValue*$request->count;
                    }
                }

                if($itemdata->item_type == 1)
                {
                    $hp = $status;
                }
                else if($itemdata->item_type == 2)
                {
                    $mp = $status;
                }
            }

            if($itemcount == 0)
            {
                $playerItem->playerItemDelete($id,$request->itemId);
            }
            else
            {
                $playerItem->playerItemUpdate($id,$request->itemId,$itemcount);
            }

            $player->playerUpdate($id,$playerdata["name"],$hp,$mp,$playerdata["money"]);

            return new Response([
                "itemId"=>$request->itemId,"count"=>$itemcount,
                "player"=>["id"=>(int)$id,
                "hp"=>$hp,"mp"=>$mp]
            ]);
        }
        catch(Exception $e)
        {
            return ["message"=>$e->getMessage()];
        }
        
    }

    /**
     * countの回数ガチャを引き、結果手に入れたアイテムを加算する
     * 
     * @param int id
     * @return \Illuminate\Http\Response
     */
    public function useGacha($id,Request $request)
    {
        $player = new Player();
        $item = new Item();
        $playerItem = new PlayerItem();
        try
        {
            $playerdata = $player->playerGet($id);

            if($playerdata == null){
                throw new Exception('no playerdata');
            }

            $money = $playerdata["money"];

            $allitemdata = $item->itemIndex();
            if($allitemdata == null)
            {
                throw new Exception('no itemdatas');
            }
            $allitemcount = count($allitemdata);

            $results = array();
            $percents = array();
            $itemcounts = array();
            $percentSUM = 0;

            for($i=0;$i<$allitemcount;$i++)
            {

                if($playerItem->playerItemExists($id,$i+1))
                {
                    $itemcounts[] = $playerItem->playerItemGet($id,$i+1)["itemcount"];
                }
                else
                {
                    $playerItem->playerItemCreate($id,$i+1,0);
                    $itemcounts[] = 0;
                }

                $results[] = 0;
                $percents[] = $allitemdata[$i]["percent"];
                $percentSUM += $allitemdata[$i]["percent"];

            }

            $price = 10;

            if($percentSUM > 100)
            {
                throw new Exception('percent error');
            }
            else
            {
                $percentSUM = 0;

                if($money<$price*$request->count)
                {
                    throw new Exception('no money error');
                }
                else
                {
                    for($i=0;$i<$request->count;$i++)
                    {
                        $gachaResult = mt_rand(0,100);
                        for($j=0;$j<$allitemcount;$j++){
                            $percentSUM += $percents[$j];
                            if($gachaResult<$percentSUM)
                            {
                                $results[$j]++;
                                $itemcounts[$j]++;
                                break;
                            }
                        }
                    }
                    $money-=$price*$request->count;
                    $player->playerUpdate($id,$playerdata["name"],
                    $playerdata["hp"],$playerdata["mp"],$money);

                    $resultdatas = array();
                    $playerItems = array();

                    for($i=0;$i<$allitemcount;$i++)
                    {
                        $playerItem->playerItemUpdate($id,$i+1,$itemcounts[$i]);
                        if($results[$i]!=0)
                        {
                            $resultdatas[]=["itemId"=>$i+1,"count"=>$results[$i]];
                        }
                        if($itemcounts[$i]!=0)
                        {
                            $playerItems[]=["itemId"=>$i+1,"count"=>$itemcounts[$i]];
                        }
                    }

                    return new Response([
                        "results"=>$resultdatas,
                        "player"=>["money"=>$money,"items"=>$playerItems]
                    ]);
                }  
            }
        }
        catch(Exception $e)
        {
            return ["message"=>$e->getMessage()];
        }
    }
}
