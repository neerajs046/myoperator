<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\ItemDetails;
use App\ItemBids;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class BiddingController extends Controller
{
	/**
	 * This action will be used for creating new items. In this Taking item_name, starting_price(Initial price)
	 * and expiry_time(in seconds)
	 * @param  Request $request Request object
	 * @return Json Response
	 */
    public function createItem(Request $request){
    	date_default_timezone_set("Asia/Calcutta");  
    	try {
    		$validator = Validator::make($request->all(), [
	    		'item_name' => 'required|string',
	    		'starting_price' => 'required',
	    		'expiry_time' => 'required', 
	    	]);

	    	if ($validator->fails()) {
	    		return response()->json(['code' => 422, 'message' => $validator->errors()], 422);
	    	}

	    	$itemModelObj = new ItemDetails;
	    	$itemModelObj->item_name = $request->item_name;
	    	$itemModelObj->starting_price = $request->starting_price;
	    	$itemModelObj->expiry_time = $request->expiry_time;
	    	$itemModelObj->is_available_for_bid = true;
	    	$itemModelObj->created_by = $request->user()->id;
	    	$itemModelObj->save();

	    	return response()->json(['code' => 200, 'message' => 'Item has been created successfully'],200);	
    	}
    	catch (\Exception $e) {
    		return response()->json(['code' => 500, 'message' => $e->getMessage()],500);
    	}
    		
    }

    /**
     * In this action will return the json object of the requested item. Checking if the item is available
     * if yes - returning the items json object else 502 error code
     * @param  integer $item_id Id of the item whose information is required
     * @return Json Object
     */
    public function getItem($item_id){
    	try {
    		$validator = Validator::make(['item_id' => $item_id], [
	    		'item_id' => 'required|integer',
	    	]);
	    	if ($validator->fails()) {
	    		return response()->json(['code' => 422, 'message' => $validator->errors()], 422);
	    	}

	    	$item = ItemDetails::find($request->item_id);
	    	if(!empty($item)){
	    		return response()->json(['code' => 200, 'data' => ['item' => $item]],200);	
	    	}
	    	else{
	    		return response()->json(['code' => 502,'message' => 'No Item Found'],502);
	    	}	
    	} catch (\Exception $e) {
    		return response()->json(['code' => 500, 'message' => $e->getMessage()],500);
    	}
    		
    }

    /**
     * This action will return the Remaining bidding Time of an item
     * @param  Request $request 
     * @param  Integer  $item_id 
     * @return Json Object
     */
    public function getItemBiddingTime(Request $request, $item_id){
    	date_default_timezone_set("Asia/Calcutta");  
    	try {
    		$validator = Validator::make(['item_id' => $item_id], [
	    		'item_id' => 'required|integer',
	    	]);

	    	if ($validator->fails()) {
	    		return response()->json(['code' => 422, 'message' => $validator->errors()], 422);
	    	}

	    	$itemBiddingTime = ItemDetails::find($request->item_id);
	    	
	    	$biddingTime = strtotime($itemBiddingTime->created_at);
	    	$biddingTime = $biddingTime+$itemBiddingTime->expiry_time;

	    	$remainingTime = $biddingTime - time();

	    	if($remainingTime < 0){
	    		$remainingTime = 0;
	    	}
	    	if(!empty($itemBiddingTime)){
	    		return response()->json(['code' => 200, 'data' => ['remainingTime' => $remainingTime]],200);
	    	}
	    	else{
	    		return response()->json(['code' => 502, 'message' => 'No Item Found'], 502);
	    	}	
    	} catch (\Exception $e) {
    		return response()->json(['code' => 500, 'message' => $e->getMessage()],500);
    	}
    }

    /**
     * This action will mark the item complete and the item will not be available for bidding
     * @param  Request $request [description]
     * @param  Integer  $item_id
     * @return Json Response
     */
    public function markBiddingComplete(Request $request, $item_id){
    	try {
    		$validator = Validator::make(['item_id' => $item_id], [
	    		'item_id' => 'required|integer',
	    	]);
	    	if ($validator->fails()) {
	    		return response()->json(['code' => 422, 'message' => $validator->errors()], 422);
	    	}

	    	$item = ItemDetails::find($request->item_id);
	    	if(!empty($item)){
	    		$item->is_available_for_bid = false;
	    		$item->save();
	    		return response()->json(['code' => 200, 'message' => 'Item has been marked complete'],200);
	    	}
	    	else{
	    		return response()->json(['code' => 502, 'message' => 'No Item Found'], 502);
	    	}
    	} catch (Exception $e) {
    		return response()->json(['code' => 500, 'message' => $e->getMessage()],500);
    	}
    	
    }

    /**
     * This action will be used for placing the bid against and item. Here First Checking if item is available
     * for bidding or not, then checking for the price,then time for bidding
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function placeBid(Request $request){
    	try {
    		$validator = Validator::make($request->all(), [
	    		'item_id' => 'required|integer',
	    		'bidding_amount' => 'required|integer',
	    		'user_id' => 'required|integer'
	    	]);
	    	if ($validator->fails()) {
	    		return response()->json(['code' => 422, 'message' => $validator->errors()], 422);
	    	}

	    	$item = ItemDetails::find($request->item_id);
	    	if(empty($item)){
	    		return response()->json(['code' => 502,'message'=>'No Item Available'],502);
	    	}
	    	else{
	    		if($item->is_available_for_bid){
	    			if($request->bidding_amount < $item->starting_price){
			    		return response()->json(['code' => 400, 'message' => 'price is less than bid amount'],400);
			    	}


					$biddingTime = strtotime($item->created_at);
			    	$biddingTime = $biddingTime+$item->expiry_time;

			    	$remainingTime = $biddingTime - time();		    	
			    	if($remainingTime < 0){
			    		return response()->json(['code' => 400, 'message' => 'Bidding time exceeded'],400);
			    	}

		    		$itemBid = new ItemBids;
		    		$itemBid->user_id = $request->user_id;
		    		$itemBid->bidding_amount = $request->bidding_amount;
		    		$itemBid->bid_at = time();
		    		$itemBid->item_id = $request->item_id;
		    		$itemBid->save();
		    		return response()->json(['code' => 200,'message'=>'Your Bid has been placed successfully'],200);
	    		}
	    		else{
	    			return response()->json(['code' => 400, 'message' => 'Item is not available for bidding'],400);
	    		}    		
	    	}	
    	} catch (Exception $e) {
    		return response()->json(['status' => 'error', 'message' => $e->getMessage()],500);
    	}
    }

    /**
     * [getFinalBidPrice description]
     * @return [type] [description]
     */
    public function getFinalBidPrice(){
    	try {
    		$finBidprice = ItemBids::orderBy('bidding_amount', 'desc')->first();
    		return response()->json(['code' => 200,'data' => ['ItemDetails' => $finBidprice]]);
    	} catch (\Exception $e) {
    		return response()->json(['code' => 500,'message' => $e->getMessage()],500);
    	}
    	
    }
}
