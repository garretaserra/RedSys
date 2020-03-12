<?php

//Include Library
include 'apiRedsysWs.php';

function getTagContent($tag, $xml){
	$retorno=NULL;

	if($tag && $xml){
		$ini=strpos($xml, "<".$tag.">");
		$fin=strpos($xml, "</".$tag.">");
		if($ini!==false && $fin!==false){
			$ini=$ini+strlen("<".$tag.">");
			if($ini<=$fin){
				$retorno=substr($xml, $ini, $fin-$ini);
			}
		}
	}
		
	return $retorno;
}

//Create Object
$miObj = new RedsysAPIWs;

$fuc="999008881";   //Merchant Code
$terminal="001";    //Merchant Terminal
$moneda="978";      //Currecy according to ISO 4217
$trans="A";         //Transaction Type

$pan="4548812049400004";    //Card number
$cvv="123";         //Security Code
$expire="2012";     //Expiration date YYMM
$url="";            //URL of business (optional)
	
$id=time();
$amount="100";      //Ammount of transaction (last 2 numbers are decimals and cents)


//Input XML
//Default tags: $datosEnt="<DATOSENTRADA><DS_MERCHANT_MERCHANTNAME>Comercio de Prue</DS_MERCHANT_MERCHANTNAME><DS_MERCHANT_AMOUNT>200</DS_MERCHANT_AMOUNT><DS_MERCHANT_CURRENCY>978</DS_MERCHANT_CURRENCY><DS_MERCHANT_MERCHANTURL>".$url."</DS_MERCHANT_MERCHANTURL><DS_MERCHANT_TRANSACTIONTYPE>".$trans."</DS_MERCHANT_TRANSACTIONTYPE><DS_MERCHANT_TERMINAL>871</DS_MERCHANT_TERMINAL><DS_MERCHANT_MERCHANTCODE>999008881</DS_MERCHANT_MERCHANTCODE><DS_MERCHANT_ORDER>".$id."</DS_MERCHANT_ORDER><DS_MERCHANT_PAN>4548810000000003</DS_MERCHANT_PAN><DS_MERCHANT_EXPIRYDATE>4912</DS_MERCHANT_EXPIRYDATE><DS_MERCHANT_CVV2>123</DS_MERCHANT_CVV2></DATOSENTRADA>";
//Edited tags
$datosEnt="<DATOSENTRADA><DS_MERCHANT_AMOUNT>".$amount."</DS_MERCHANT_AMOUNT><DS_MERCHANT_CURRENCY>".$moneda."</DS_MERCHANT_CURRENCY><DS_MERCHANT_TRANSACTIONTYPE>".$trans."</DS_MERCHANT_TRANSACTIONTYPE><DS_MERCHANT_TERMINAL>".$terminal."</DS_MERCHANT_TERMINAL><DS_MERCHANT_MERCHANTCODE>".$fuc."</DS_MERCHANT_MERCHANTCODE><DS_MERCHANT_ORDER>".$id."</DS_MERCHANT_ORDER><DS_MERCHANT_PAN>".$pan."</DS_MERCHANT_PAN><DS_MERCHANT_EXPIRYDATE>".$expire."</DS_MERCHANT_EXPIRYDATE><DS_MERCHANT_CVV2>".$cvv."</DS_MERCHANT_CVV2></DATOSENTRADA>";


$kc = 'sq7HjrUOBfKmC576ILgskD5srU870gJ7';   //Key for signature to check autentication
$nuevaFirma = $miObj->createMerchantSignatureHostToHost($kc,$datosEnt); //Create a signature using the key

$nuevaEntrada = "<REQUEST>".$datosEnt."<DS_SIGNATUREVERSION>HMAC_SHA256_V1</DS_SIGNATUREVERSION><DS_SIGNATURE>".$nuevaFirma."</DS_SIGNATURE></REQUEST>";
//print "<xmp>".$nuevaEntrada."</xmp>";

  $soap_request  = "<?xml version=\"1.0\"?>\n";
  $soap_request .= '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:web="http://webservice.sis.sermepa.es">';
  $soap_request .= '<soapenv:Header/>';
  $soap_request .= '<soapenv:Body>';
  $soap_request .= '<web:trataPeticion>';
  $soap_request .= '<web:datoEntrada><![CDATA['.$nuevaEntrada.']]></web:datoEntrada>';
  $soap_request .= '</web:trataPeticion>';
  $soap_request .= '</soapenv:Body>';
  $soap_request .= '</soapenv:Envelope>';

  
  $header = array(
    "Content-type: text/xml;charset=\"utf-8\"",
    "Accept: text/xml",
    "Cache-Control: no-cache",
    "Pragma: no-cache",
    "SOAPAction: \"run\"",
    "Content-length: ".strlen($soap_request),
  );
 
  $soap_do = curl_init();
  curl_setopt($soap_do, CURLOPT_URL, "https://sis-i.redsys.es:25443/sis/services/SerClsWSEntrada" );
  curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 10);
  curl_setopt($soap_do, CURLOPT_TIMEOUT,        10);
  curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
  curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($soap_do, CURLOPT_POST,           true );
  curl_setopt($soap_do, CURLOPT_POSTFIELDS,     $soap_request);
  curl_setopt($soap_do, CURLOPT_HTTPHEADER,     $header);
	$data = curl_exec($soap_do);
 
  if($data === false) {
    $err = 'Curl error: ' . curl_error($soap_do);
    curl_close($soap_do);
    print $err;
  } else {
  	$tag = array ();
  	preg_match ( "/<p[0-9]+:trataPeticionReturn>/", $data, $tag );
  	$result = htmlspecialchars_decode ( getTagContent ( str_replace ( "<", "", str_replace ( ">", "", $tag [0] ) ), $data ) );

	//print "<xmp>".$result."</xmp>";
    curl_close($soap_do);

    $signatureValues = $miObj->getTagContent($result,"Ds_Amount");
    $signatureValues .= $id;
    $signatureValues .= $miObj->getTagContent($result,"Ds_MerchantCode");
    $signatureValues .= $miObj->getTagContent($result,"Ds_Currency");
    $signatureValues .= $miObj->getTagContent($result,"Ds_Response");
    $signatureValues .= $miObj->getTagContent($result,"Ds_TransactionType");
    $signatureValues .= $miObj->getTagContent($result,"Ds_SecurePayment");
  }
?>