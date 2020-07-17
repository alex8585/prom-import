<div class='order-details-wrpapper'>
<table class='order-table wp-list-table widefat fixed striped pages'>
    <tr>
        <td>Наименование предприятия:</td> 
        <td><?php echo $order->company_name ?></td>
    </tr>
    <tr>
        <td>Телефон:</td> 
        <td><?php echo $order->phone ?></td>
    </tr>
    <tr>
        <td>Email:</td> 
        <td><?php echo $order->mail ?></td>
    </tr>
    <tr>
        <td>Реквизиты:</td> 
        <td><?php echo $order->requisite ?></td>
    </tr>
    
    <tr>
        <td>Дополнительно:</td> 
        <td><?php echo $order->additionally ?></td>
    </tr>
    <?php if($order->requisite_file): ?>
        <tr>
            <td>Файл:</td> 
            <td><?php echo $order->requisite_file ?></td>
        </tr>
    <?php endif;?>
</table>


<table  class="products-table order-table wp-list-table widefat fixed striped pages">
    <thead>
        <tr class="row-1 odd">
            <th>
                <div>№ п/п.</div>
            </th>
            <th>
                <div>Наименование продукции</div>
            </th>
            <th>
                <div>Цена без НДС, руб.</div>
            </th>
            <th>
                <div>Ставка НДС, % </div>
            </th>
            <th>
                <div> НДС, руб.</div>
            </th>
            <th>
                <div>Цена с НДС, руб.</div>
            </th>
            <th>
                <div>Вес</div>
            </th>
            <th>
                <div>Количество</div>
            </th>
            <th>
                <div>Сумма, руб.</div>
            </th>
        </tr>
    </thead>
   

    <tbody class="row-hover">
        <?php
        $nds =  $rows[0]->nds *100;
        $orderTotal = 0;
        $orderTotalNds = 0;
        $orderWeight =0;
        foreach($rows as $r): ?>
            <?php 
                $orderTotal += $r->price*$r->quantity;
                $orderTotalNds += $r->price_nds*$r->quantity;
                $orderWeight += $r->weight*$r->quantity;
            ?></td>
            <tr class="product-row row-2 even">
                <td class="td-row-id" style="display:none;" row_id="<?php echo $r->id?>" ></td>

                <td>
                <?php echo $r->number ?></td>
                <td><?php echo $r->name ?> </td>
                <td class='td-price'  price="<?php echo $r->price?>" ><?php echo number_format($r->price, 2, '.', '') ?> </td>
                <td><?php echo ($r->nds*100 )?> </td>
                <td  >
                    <?php
                        $nds_sum =  $r->price * $r->nds;
                        echo number_format($nds_sum, 2, '.', '');
                    ?> 
                </td>
                <td class='td-price-nds' price="<?php echo $r->price_nds?>">
                    <?php  echo number_format($r->price_nds, 2, '.', ''); ?> 
                </td>
                <td class='td-weight' weight="<?php echo $r->weight?>">
                    <?php  echo $r->weight; ?> 
                </td>
                <td>
                    <?php  echo $r->quantity; ?>  
                </td>
                <td class="td-row-total" quantity='0' price_nds="" price="" weight="">
                    <?php  echo number_format($r->price_nds*$r->quantity, 2, '.', ''); ?> 
                </td>
            </tr>
        <?php endforeach;?>

    </tbody>
</table>

    <div class='order-total-wrapper'>
        <div>
            <span>Сумма без НДС</span>
            <span class="order-total">
                <?php  echo number_format($orderTotal, 2, '.', ''); ?> 
            </span> 
            <span>руб.</span>
        </div>
        <div>
            <span >НДС</span>
            <span class="order-nds">
                <?php echo $nds?>
            </span> 
            <span>%</span>
        </div>
        <div>
            <span>Сума с НДС</span>
            <span class="order-total-nds">
                <?php  echo number_format($orderTotalNds, 2, '.', ''); ?> 
            </span>
            <span>руб.</span>
        </div>
        <div>
            <span class="">вес</span>
            <span class="order-total-weight">
                <?php  echo number_format($orderWeight , 2, '.', ''); ?> 
            </span> 
        </div>
    </div>
</div>    
</div>