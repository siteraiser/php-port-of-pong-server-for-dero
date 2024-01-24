<?php 
/* PHP Dero Pong Server Port by Crazy Carl T. 
Fill in the appropriate values to create an integrated dero address.
When someone buys using the link the pong server will respond when it detects a new sale.
The response can include a return amount and a return message up to 144 bytes.
A smart contract can also be defined to transfer that as well.

This demo only allows for the currently defined product to respond to sales (one product at a time).

If you change the price and delete all of the records it checks to make sure you are using the current price so at least all of the transactions of any other price will be skipped until you use the same price again.
Todo: create more fields to check so that you can delete the records without reprocessing all of the previous transactions (for the amount specifed). 

*/ 
set_time_limit(0);//infinite
class UUID {
	//Thank you commenters in the PHP docs
  public static function v4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

      // 32 bits for "time_low"
      mt_rand(0, 0xffff), mt_rand(0, 0xffff),

      // 16 bits for "time_mid"
      mt_rand(0, 0xffff),

      // 16 bits for "time_hi_and_version",
      // four most significant bits holds version number 4
      mt_rand(0, 0x0fff) | 0x4000,

      // 16 bits, 8 bits for "clk_seq_hi_res",
      // 8 bits for "clk_seq_low",
      // two most significant bits holds zero and one for variant DCE1.1
      mt_rand(0, 0x3fff) | 0x8000,

      // 48 bits for "node"
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
  }
}

function connectionErrors($ch){
	// Check HTTP status code
	if (!curl_errno($ch)) {
	  switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
		case 200:  # OK
		  break;
		default:
		  outputMessageNow('<br>Unexpected HTTP code: '. $http_code);
	  }
	}else{
		outputMessageNow("<br>".curl_error($ch) . ' ' . curl_errno($ch));
	}
}


//API funtions

//Creates a new integrated address
//When used as a send to address it will display the in message and fill in the correct amounts as well as allowing a port to be defined (not actually used here)
function export_iaddress($ip,$port,$user,$pass,$d_port,$in_message,$ask_amount){
	$data = '{
		"jsonrpc": "2.0",
		"id": "1",
		"method": "MakeIntegratedAddress",
		"params": {
		  "payload_rpc": [
			{
			  "name": "C",
			  "datatype": "S",
			  "value": "'.$in_message.'"
			},
			{
			  "name": "D",
			  "datatype": "U",
			  "value": '.$d_port.'
			},
			{
			  "name": "N",
			  "datatype": "U",
			  "value": 0
			},
			{
			  "name": "V",
			  "datatype": "U",
			  "value": '.$ask_amount.'
			}
		  ]
		}
	}';

$json = json_decode($data,true);
$json = json_encode($json);

	$ch = curl_init("http://$ip:$port/json_rpc");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
		"Authorization: Basic " . base64_encode($user.':'.$pass),
		"Content-Type: application/json"
	]);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	$output = curl_exec($ch);
	
	connectionErrors($ch);
	
	curl_close($ch);

	return $output;

}

//Gets the list of incoming transfers
function export_transfers($ip,$port,$user,$pass){
	$data = '{
		"jsonrpc": "2.0",
		"id": "1",
		"method": "GetTransfers",
		"params": {
		  "out": false,
		  "in": true
		}
	}';

$json = json_decode($data,true);
$json = json_encode($json);

	$ch = curl_init("http://$ip:$port/json_rpc");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
		"Authorization: Basic " . base64_encode($user.':'.$pass),
		"Content-Type: application/json"
	]);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	$output = curl_exec($ch);
	
	connectionErrors($ch);

	curl_close($ch);

	return $output;

}

//Creates a transfer to respond to new sales (destination address). 
//If a smart contract is specified it can transfer that. 
//If a respond amount is specified it will send that.
//You have 144 by for an out message (link or uuid etc).
function payload($ip, $port, $user, $pass, $respond_amount, $addr,  $scid, $out_message){	
	
	$data = '{
    "jsonrpc": "2.0",
    "id": "1",
    "method": "transfer",
    "params": {
       "ringsize": 16,
       "transfers":
       [
        {
          "scid": "'.$scid.'",
          "destination": "'.$addr.'",
          "amount": '.$respond_amount.',
          "payload_rpc":
          [
            {
              "name": "C",
              "datatype": "S",
              "value": "'.$out_message.'"
            }
          ]
        }
      ]
    }
  }';

$json = json_decode($data,true);
$json = json_encode($json);

	$ch = curl_init("http://$ip:$port/json_rpc");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
		"Authorization: Basic " . base64_encode($user.':'.$pass),
		"Content-Type: application/json"
	]);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	$output = curl_exec($ch);	
	
	connectionErrors($ch);

	return $output;

}


$UUID = new UUID;

// Initialize Environment Variables
$ask_amount="6543"; // this is how much you want coming in
$respond_amount="2"; // this is how much is going out
$ip = "127.0.0.1";//127.0.0.1:10103 (for Engram cyberdeck)
$port="10103";
$user="secret";
$pass="pass";
$in_message="You are buying something super great";
$d_port="24862";//not being used currently
$uuid=$UUID->v4();
$out_message=$uuid;

$scid="0000000000000000000000000000000000000000000000000000000000000000";


if (!file_exists('assets/')) {
    mkdir('assets/', 0777, true);
}

$pong_dir="assets/";
$pong_db="$pong_dir/$ask_amount.sales.db";
$iaddress_text_file="$pong_dir/$ask_amount.iaddress.txt";
//$iaddress_qr_file="$pong_dir/$ask_amount.iaddress_qrcode.png";

if (!file_exists($pong_db)) {
     touch($pong_db);
}

$export_address_result =  export_iaddress($ip,$port,$user,$pass,$d_port,$in_message,$ask_amount);

outputMessageNow("Welcome to your pong server.<br>");
$export_address_result = json_decode($export_address_result);
if($export_address_result !=''){
	file_put_contents($iaddress_text_file, $export_address_result->result->integrated_address);
	outputMessageNow("Your integrated address is below:<br>");

	$iaddress = file_get_contents($iaddress_text_file);
	//qrencode -o $iaddress_qr_file "$iaddress"
	outputMessageNow($iaddress."<br>");
	outputMessageNow("A copy of your integrated address has been saved in $pong_dir as a txt file<br>");// and a qr code... try shell_exec() in linux.
	outputMessageNow("Already processed transactions found in the database will be skipped automatically<br>");
}else{
	outputMessageNow("Can't Get Integrated Address:<br>");
}


//Begin the inifnite loop to check for new transactions not yet processed (and saved to db)
$count=0;
while($count++ < 3){	//set to true to run forever 
	$export_transfers_result =	export_transfers($ip,$port,$user,$pass);
	$export_transfers_result = json_decode($export_transfers_result);
	
	if($export_transfers_result == null){
		outputMessageNow("<br> Error Checking Account");
		sleep(18);
		continue;
	}


	
	//Open the stored data
	$storage_array = json_decode(file_get_contents("$pong_db"));
	if($storage_array == '' ){
		$storage_array = [];
	}
	foreach($export_transfers_result->result->entries as $entry){
		
		
		//See if there is a payload
		if(isset($entry->payload_rpc)){
			
			$save_sale = false;			
			//Ensure that we are referring to the same amount when no sales are found in db.
			if(empty($storage_array) && $ask_amount == $entry->amount){
				$save_sale = true;
			}else{	
				//There are sales in the storage file / db, compare them to the list returned from the wallet_rpc
				$txfound= false;
				foreach($storage_array as $saved){					
					if(  					
						$saved->txid != $entry->txid &&
						$saved->time != $entry->time && 
						$saved->amount != $entry->amount && //seems flawed, maybe some work to do here so that the price can stay the same.
						$saved->address != $entry->address		
					){
						//No matching sales, save it
						$save_sale = true;
					}					
				}
			}
			
			
			if($save_sale){
				
				outputMessageNow('<br>Saving Sale');				
				
				//Find buyer address in payload
				foreach($entry->payload_rpc as $payload){
					if($payload->name == "R" && $payload->datatype == "A"){
						$address = $payload->value;
					}				
				}	
				//Send Reponse to buyer
				$payload_result = payload($ip, $port, $user, $pass, $respond_amount, $address, $scid, $out_message);
				$payload_result = json_decode($payload_result);
			
				//Ensure that the response transfer is successful
				if($payload_result != null && $payload_result->result){
					outputMessageNow("<br>Sent uuid as out message:".$out_message);
					outputMessageNow("<br>txid:".$payload_result->result->txid);
					//Save if successful
					$storage_array[] = (object)[
						"time"=>$entry->time,
						"amount"=>$entry->amount,
						"address"=>$address,
						"txid"=>$entry->txid
					];
					//Save the sales list
					file_put_contents("$pong_db",json_encode($storage_array));
					
				}else{
					outputMessageNow("<br>An error occurred sending response.");					
				}
			}			
		}
	}	
	

	sleep(18);
}


function outputMessageNow($message){
	ob_end_flush();
	ob_start();
	ob_implicit_flush();		
	echo $message;					
	ob_flush();
	flush();
	
}
