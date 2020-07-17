<h1>Заказы</h1>
<div class='order-details-wrpapper'>
 <table class="order-table wp-list-table widefat fixed striped pages">
    <thead>
        <tr class="row-1 odd">
            <th>
                <div>Номер заказа</div>
            </th>
            <th>
                <div>Наименование предприятия:</div>
            </th>
            <th>
                <div>E-mail:</div>
            </th>
            <th>
                <div>Телефон:</div>
            </th>
            
            <th>
                <div>Дайствия</div>
            </th>
        </tr>
    </thead>
   
   
    <tbody class="row-hover">
        <?php 
       
        foreach($orders as $o): ?>
            <tr class="">
                <td class="order-row-id"  row_id="<?php echo $o->id?>" ># <?php echo $o->id?></td>
                <td> <?php echo $o->company_name; ?> </td>
                <td> <?php echo $o->mail; ?> </td>
                <td> <?php echo $o->phone; ?> </td>
                <td> <a href="<?php echo $products_page.'&id='.$o->id;?>">Подробно</a> </td>
            </tr>
        <?php endforeach;?>

    </tbody>
</table>
 <div class="pagination-orders">
    <?php if($paginator['prev_page_num']): ?>
        <a class='prev-page button' href="<?php echo $orders_page.'&p='.$paginator['prev_page_num'];?>"> 
            <span aria-hidden="true">‹</span>
        </a>
    <?php endif; ?>
    <?php if($paginator['next_page_num']): ?>
        <a class='next-page button' href="<?php echo $orders_page.'&p='.$paginator['next_page_num'];?>"> 
            <span aria-hidden="true">›</span> 
        </a>
    <?php endif; ?>
 </div>

</div>