<?php
	//Stores all information
	$list = array ( );
	//Headline
	array_push($list, array('Verkaufsprotokollnummer', 'Mitgliedsname', 'Vollst�ndiger Name des K�ufers', 'E-Mail des K�ufers', 'K�uferadresse 1', 'K�uferadresse 2', 
			'Ort des K�ufers', 'Staat des K�ufers', 'Postleitzahl des K�ufers', 'Land des K�ufers', 
			'Bestellnummer', 'Artikelnummer', 'Transaktions-ID', 'Artikelbezeichnung', 'St�ckzahl', 
			'Verkaufspreis', 'Inklusive Mehrwertsteuersatz', 'Verpackung und Versand', 'Versicherung', 'Gesamtpreis', 
			'Zahlungsmethode', 'PayPal Transaktions-ID', 'Rechnungsnummer', 'Rechnungsdatum', 'Verkaufsdatum', 'Kaufabwicklungsdatum', 'Bezahldatum', 'Versanddatum', 
			'Versandservice', 'Abgegebene Bewertungen', 'Erhaltene Bewertungen', 'Notizzettel', 'Bestandseinheit', 'Private Notizen', 'Produktkennung-Typ', 'Produktkennung-Wert', 'Produktkennung-Wert 2', 
			'Variantendetails', 'Produktreferenznummer', 'Verwendungszweck', 'Sendungsnummer', 'eBay Plus', 'Nebenkosten'));


	if ($entries == 0) {
		echo $now." NO new orders from " . $CreateTimeFrom . " to " . $CreateTimeTo;
	}
	else{
	$orders = $response->OrderArray->Order;
	
    if ($orders != null) {
		echo $now." New orders get parsed.";
		
        foreach ($orders as $order) {
				$shippingAddress = $order->ShippingAddress;
				$ShippingServiceSelected = $order->ShippingServiceSelected;
				$externalTransaction = $order->ExternalTransaction;
				$checkoutmessage = $order->BuyerCheckoutMessage;
				$checkoutmessage = preg_replace('/\s+/', ' ', trim($checkoutmessage)); //Our ERP-system has problems with \r\n in messages. This removes them.
				
				$transactions = $order->TransactionArray;
                if ($transactions) {
                    // iterate through each transaction for the order
					$i = 0;
                    foreach ($transactions->Transaction as $transaction) {	
						$title = $transaction->Item->Title;
						$quantity = $transaction->QuantityPurchased;
						$price = $transaction->TransactionPrice;
						$fees = $externalTransaction->FeeOrCreditAmount;
						$paymentID = $order->ExternalTransaction->ExternalTransactionID;
						//Packs with multiple items?
						if(strpos(strtolower($title), 'er pack')){
							$strpostitle = substr($title,0,strpos(strtolower($title),"er pack")); //Cut everything after "er Pack"
							$lastspace = strrpos($strpostitle, ' '); //Search for last space
							if($lastspace > 0){
								$strpostitle = substr($strpostitle, $lastspace, strlen($strpostitle)); //Cut everything before last space
							}	
							$quantity *= intval($strpostitle); //Get "real" quantity
							$price = doubleval($price) / doubleval($strpostitle); //Get "real" price
							$fees = (doubleval($fees)-0.25) / doubleval($strpostitle) + 0.01; //Get "real" fees
						}
						if($quantity > 1 || $i == 1){
							$fees = 0;
						}
						$i = 1; //Fees should only be imported once
						
						//On some transactions the paymentID gets set to "SIS". We don't want this.
						if($paymentID == "SIS"){
							$paymentID = "";
						}
						
					
						array_push($list, array($order->OrderID, $order->BuyerUserID, $shippingAddress->Name, $transaction->Buyer->Email, $shippingAddress->Street1, $shippingAddress->Street2, 
						$shippingAddress->CityName, $shippingAddress->StateOrProvince, $shippingAddress->PostalCode, $shippingAddress->CountryName,
						$transaction->OrderLineItemID, $transaction->Item->SKU, $transaction->TransactionID, $transaction->Item->Title, $quantity,
						$price, $order->ShippingDetails->SalesTax->SalesTaxAmount, $ShippingServiceSelected->ShippingServiceCost, "0,00", $order->AmountPaid,
						$order->CheckoutStatus->PaymentMethod, $paymentID, '', '', $externalTransaction->ExternalTransactionTime, $externalTransaction->ExternalTransactionTime, $externalTransaction->ExternalTransactionTime, '', 
						$ShippingServiceSelected->ShippingService, 'Nein', '', '', $transaction->Item->SKU, $checkoutmessage, '', '', '',
						'', '', '', '', 'Nein', $fees, $shippingAddress->Country));
                    }
                }
        }
    }else{
		echo $now." NO new orders.";
	}
	
		//Write the transactions to file

		$fp = fopen('ebayOrder.csv', 'w');

		for ($i = 0; $i < count($list); $i++) {
			fputcsv($fp, $list[$i], ';');
		}

		fclose($fp);
		
		
		$fp = fopen('last.txt', 'w+');
		fwrite($fp, $CreateTimeTo);
		fclose($fp);
	}
		
?>