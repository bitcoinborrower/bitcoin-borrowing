<!DOCTYPE html>
<html lang="en">
<head>
    <?php    
   // open mysql connection
    $host = "localhost";
    $username = "";
    $password = "";
    $dbname = "";
    $con = mysqli_connect($host, $username, $password, $dbname) or die('Error in Connecting: ' . mysqli_error($con));    
    ?>  
  <title>Tables</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>
<body>
    <?php   
if($_GET){    
         //fetch todays btc price   
        $data = file_get_contents('https://min-api.cryptocompare.com/data/price?fsym=BTC&tsyms=BTC,USD');
        $pieces = explode(":", $data);
        //set todays price variable
        $todays_price =str_replace("}", "", $pieces[2]); // piece2 
        //set posted variables  
        $months=$_GET[months]; //months to backtest
        $borrowed=$_GET[borrow]; // amount to borrow
        $ir=$_GET[ir]; //interest rate
        $ltv=$_GET[ltv]; // LVT
        $stash=$_GET[stash]; // Amount of bitcoin held
        $starting=strtotime($_GET[start]); // date to start backtest
}
    ?>
    <center><h1>Bitcoin Collateral Borrowing</h1></center>
             <form action="">
                <div class="table-responsive">  
                     <center>
                        <table class="table-condensed" width="80%" style="font-size:10px">   
                            <tr>
                                <td width="100%"> 
                                    <div class="container" style="margin-top:50px;">
                                        <div class="row">
                                            <div class="col-sm-4" style="background-color:white;">
                                            <label for="start">Backtest start date:</label>
                                            <input type="date" id="start" name="start" required>
                                            </div>
                                            <div class="col-sm-4" style="background-color:white;">
                                            <label for="stash">Months to back test:</label>
                                            <input type="text" id="months" name="months" size="5"  <?php if($_GET[months]){ echo 'value="'.$months.'"';}else{echo 'placeholder="12" ';} ?> required>
                                            </div> 
                                            <div class="col-sm-4" style="background-color:white;">
                                            <label for="stash">BTC Stash:</label>
                                            BTC<input type="text" id="stash" name="stash" size="5"  <?php if($_GET[stash]){ echo 'value="'.$stash.'"';}else{echo 'placeholder="0.00000000" ';} ?>required>
                                            </div>
                                        </div> 
                                        <div class="row">
                                            <div class="col-sm-4" style="background-color:white;">
                                            <label for="stash">Starting LTV:</label>
                                            %<input type="text" id="ltv" name="ltv" size="5"  <?php if($_GET[ltv]){ echo 'value="'.$ltv.'"';}else{echo 'placeholder="0.60" ';} ?>required>
                                            </div>
                                            <div class="col-sm-4" style="background-color:white;">
                                            Borrow $<input type="text" id="borrow" name="borrow" size="5"  <?php if($_GET[borrow]){ echo 'value="'.$borrowed.'"';}else{echo 'placeholder="1000" ';} ?>required>
                                            </div>
                                            
                                             <div class="col-sm-4" style="background-color:white;">
                                            Interest rate %<input type="text" id="ir" name="ir" size="5"  <?php if($_GET[ir]){ echo 'value="'.$ir.'"';}else{echo 'placeholder="0.10" ';} ?>required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-4" style="background-color:white;">
                                            <input type="submit" value="Submit">
                                            </div>
                                        </div> 
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </center>  
                </div>
            </form>     
  
<div class="container" style="margin-top:50px;">
 <?php
    if($_GET){  
echo '<div class="table-responsive">';


    //get all prices
    $sqlc = "SELECT * FROM `btc_api_price`";
    $resultc = $con->query($sqlc);
    $btc_prices = array();
    if ($resultc)
    {
        while($rowc = $resultc->fetch_assoc())
        {
            $btc_date = $rowc[date];
            $btc_price = $rowc[price];
            
            $btc_prices[$btc_date] = $btc_price;
        }
    }
    
// run a query to get the btc price on the start date    
    $i=0;
    while($i<1)
    {
            $m='+'.$i;
            $date = date("M-d-Y", strtotime($m." month", $starting));    
            $search_date = date("m.d.Y", strtotime($m." month", $starting));               
            if (isset($btc_prices[$search_date]))
            {
               $btc_price=$btc_prices[$search_date];
            //set the firts btc price variable
            $first_btc_price=$btc_price*$stash;    
            }     
     $i++;
    }
    
    //set backdated price factor relative to todays price   
    $backdated_relative_price=$todays_price/$btc_price; 

         //display the assumptions   
        echo '<p>The current BTC price is $'.number_format($todays_price, 0).' and you are assesing borrowing $'.number_format($borrowed, 0).'.00 backtested from the starting date of '.$search_date.' when the BTC price was $'.number_format($btc_price, 0).'.00 so  the borrowing amount has been adjusted relative to that price. The adjusted borrowing amount is $'.number_format($borrowed/$backdated_relative_price, 0).'.00</p>'; 

    //calculcate the backdated price relative to todays price    
    $backdated_borrowed=$borrowed/$backdated_relative_price;
    //calculate the collateral required in USD    
    $collateral_usd_a=($backdated_borrowed+($backdated_borrowed*$ir))/$ltv;
    $collateral_usd=number_format(($backdated_borrowed+($backdated_borrowed*$ir))/$ltv, 2); 
    //display collateral amount
    if($collateral_usd_a<$first_btc_price)
        {
        $style='<div class style="color:#000000;"><br><br>USD collatarel required: $'.number_format($collateral_usd_a, 0).'.00';
        //set continue variable
        $continue='yes'; //set continue variable 
        }
        else
        //display warning if required collateral cannot be met
        {
        $style='<div class style="color:red; font-weight:bold;"><p>USD collatarel required is $'.number_format(($backdated_borrowed+($backdated_borrowed*$ir))/$ltv, 0).'.00 which is greater than the value of your BTC stash of $'.number_format($first_btc_price*$stash, 2).'</p>
        <p>You need to adjust the borrowing amount down or increase your BTC stash.</p>';
        $continue='no'; //set continue variable 
        }    
    echo $style; 
    echo '</div>';
    
     //if continue yes
    if($continue=='yes')
        {
            // run a query to calculate btc collateral required
            $i=0;
            while($i<1)
            {
            $m='+'.$i;
            $date = date("M-d-Y", strtotime($m." month", $starting));    
            $search_date = date("m.d.Y", strtotime($m." month", $starting));  
            if (isset($btc_prices[$search_date]))
            {   
                $btc_price=$btc_prices[$search_date];
                if($i==0){ $dollar_price=($backdated_borrowed+($backdated_borrowed*$ir))/$ltv;
                    echo 'BTC collatarel required: '.number_format($dollar_price/$btc_price, 8).'<br><br>';               
                $collateral_btc=number_format($dollar_price/$btc_price, 8);      
                }
            }
            
            $i++;
        }   
    
      
    echo '<table class="table-condensed table-hover" width="100%" style="font-size:10px">
    <thead>';
    //set the date row
    echo '<tr>';
    echo '<th width="10%">Date</th>';
        $starting=strtotime($_GET[start]);
        $i=0;
        while($i<$months)
            {
            $m='+'.$i;
            $date = date("M-d-Y", strtotime($m." month", $starting));

            echo '<th>'.$date.'</th>'; 
            $i++;
            }
    echo '</tr>';


    echo' </thead>';    
    echo '<tbody>';
    
 //set the btc price row   
    echo '<tr>';
    echo '<td width="10%">BTC price</td>';
        $starting=strtotime($_GET[start]);
        $i=0;
            while($i<$months)
            {
            $m='+'.$i;
            $date = date("M-d-Y", strtotime($m." month", $starting));    
            $search_date = date("m.d.Y", strtotime($m." month", $starting));   
                if (isset($btc_prices[$search_date]))
                {   $btc_price=$btc_prices[$search_date]; 
                echo '<td><center>$'.number_format($btc_price, 0).'</center></td>';   
                }
                     
            $i++;
            }
    echo '</tr>';  
    
 // set total asset value before debt   
    
    echo '<tr>';
    echo '<td width="10%">Total asset value before debt</td>'; 
        $starting=strtotime($_GET[start]);
        $i=0;
        while($i<$months)
            {
            $m='+'.$i;
            $date = date("M-d-Y", strtotime($m." month", $starting));    
            $search_date = date("m.d.Y", strtotime($m." month", $starting));   
            if (isset($btc_prices[$search_date]))
            {   $stash=$_GET[stash]*$btc_prices[$search_date]; 
            echo '<td><center>$'.number_format($stash, 0).'</center></td>';   
            }
            $i++;
            }  
    echo '</tr>';
    
//set total debt, cumulutive+interest 
    
    echo '</tr>'; 
    echo '<tr><td width="10%">Total debt, cumulative+interest</td>';   
        $starting=strtotime($_GET[start]);
        $i=0;
        while($i<$months)
            {
            $m='+'.$i;
            $date = date("M-d-Y", strtotime($m." month", $starting));
            $debt=$backdated_borrowed+($ir*$backdated_borrowed);   
            $search_date = date("m.d.Y", strtotime($m." month", $starting));   
            if (isset($btc_prices[$search_date]))
            {   $stash=$_GET[stash]*$btc_prices[$search_date]; 
            echo '<td><center>$'.number_format($debt, 0).'</center></td>';   
            }
            $i++;
            }  
    echo '</tr>';
    
//set    Debt/equity ratio 
       
    echo '<tr>';
    echo '<td width="10%">Debt/equity ratio</td>';   
        $starting=strtotime($_GET[start]);
        $i=0;
        while($i<$months)
            {
            $m='+'.$i;
            $date = date("M-d-Y", strtotime($m." month", $starting));
            $debt=$backdated_borrowed+($ir*$backdated_borrowed);   
            $search_date = date("m.d.Y", strtotime($m." month", $starting));   
            if (isset($btc_prices[$search_date]))
            { 
            $de=$debt/($_GET[stash]*$btc_prices[$search_date]); 
            if($de<0.50){$style='class style="color:#000000;"';}
            else{$style='class style="color:red; font-weight:bold;"';}
            echo '<td '.$style.'><center>'.number_format($de, 2).'%</center></td>';   
            }
            $i++;
            }  
    echo '</tr>';

    // set  Current LVT.  
    echo '<tr><td width="10%">Current LTV.</td>';   
        $starting=strtotime($_GET[start]);
        $i=0;
            while($i<$months)
            {
            $m='+'.$i;
            $date = date("M-d-Y", strtotime($m." month", $starting));
            $debt=$backdated_borrowed+($ir*$backdated_borrowed);   
            $search_date = date("m.d.Y", strtotime($m." month", $starting));   
            if (isset($btc_prices[$search_date]))
            {  
            $current_lvt=($debt/$btc_prices[$search_date]/$collateral_btc);
            if($current_lvt<0.90){$style='class style="color:#000000;"';}
            else{$style='class style="color:red; font-weight:bold;"';}
            echo '<td '.$style.'><center>'.number_format($current_lvt, 2).'%</center></td>';   
            }
            $i++;
            }  
        echo '</tr>';   
        echo '</tbody>';
        echo'</table>'; 
        echo '</div>';
        } //$continue
        } //if(GET)
?>
    
    
    <?php   
    if(!$_GET){ // display legend
    ?>
        <div class="container-fluid" style="margin-top:20px;">
        <div class="row">
        <div class="col-sm-12" style="background-color:white;">
            <strong>Backtest start date.</strong>
            <p>Select a date in bitcoins history from which to base your borrowing backtest from. Can backtest from 11/20/2015</p>
            <strong>Months to backtest.</strong>
            <p>Select the number of months you want your backtest to display over. For example if your backtest start date was 1/1/2017 and you nominated 12 months to backtest, your test would run from January 2017 to December 2017.</p>
            <strong>BTC Stash.</strong>
            <p>The amount of bitcoin held.</p>
             <strong>Starting LTV.</strong>
            <p>The starting LTV is agreed between the borrower and lender. <a href="https://lend.hodlhodl.com/faq/basics#what_is_ltv" target="_blank">FAQ HodlHodl</a></p>
                         <strong>Borrow.</strong>
            <p>The USD amount being borrowed.</p>
                         
            <p><a href="https://lend.hodlhodl.com/faq" target="_blank">Lend HodlHodl FAQ.</a></p>
        </div>
        </div>    
    <?php
    }
    ?>    
</div>
</body>
</html>
