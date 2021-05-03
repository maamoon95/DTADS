<?php

/*
 * This file is part of the CRUD Admin Generator project.
 *
 * Author: Jon Segador <jonseg@gmail.com>
 * Web: http://crud-admin-generator.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';
require_once __DIR__.'/../../../src/utils.php';


use Symfony\Component\Validator\Constraints as Assert;

$app->match('/books/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
    $start = 0;
    $vars = $request->request->all();
    $qsStart = (int)$vars["start"];
    $search = $vars["search"];
    $order = $vars["order"];
    $columns = $vars["columns"];
    $qsLength = (int)$vars["length"];    
    
    if($qsStart) {
        $start = $qsStart;
    }    
	
    $index = $start;   
    $rowsPerPage = $qsLength;
       
    $rows = array();
    
    $searchValue = $search['value'];
    $orderValue = $order[0];
    
    $orderClause = "";
    if($orderValue) {
        $orderClause = " ORDER BY ". $columns[(int)$orderValue['column']]['data'] . " " . $orderValue['dir'];
    }
    
    $table_columns = array(
		'isbn', 
		'title', 
		'authorid', 
		'publishedate', 
		'edition', 
		'picurl', 
		'language', 
		'discribtion', 
		'price', 
		'localprice', 
		'tag', 
		'buycount', 
		'viewcount', 
		'categoryid', 
		'createdAt', 
		'updatedAt', 

    );
    
    $table_columns_type = array(
		'varchar(150)', 
		'varchar(255)', 
		'int', 
		'datetime', 
		'varchar(255)', 
		'varchar(255)', 
		'varchar(255)', 
		'longtext', 
		'int', 
		'int', 
		'json', 
		'int', 
		'int', 
		'int', 
		'datetime', 
		'datetime', 

    );    
    
    $whereClause = "";
    
    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        $whereClause =  $whereClause . " " . $col . " LIKE '%". $searchValue ."%'";
        
        $i = $i + 1;
    }
    
    $recordsTotal = $app['db']->fetchColumn("SELECT COUNT(*) FROM `books`" . $whereClause . $orderClause, array(), 0);
    
    $find_sql = "SELECT * FROM `books`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

		if( $table_columns_type[$i] != "blob") {
				$rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
		} else {				if( !$row_sql[$table_columns[$i]] ) {
						$rows[$row_key][$table_columns[$i]] = "0 Kb.";
				} else {
						$rows[$row_key][$table_columns[$i]] = " <a target='__blank' href='menu/download?id=" . $row_sql[$table_columns[0]];
						$rows[$row_key][$table_columns[$i]] .= "&fldname=" . $table_columns[$i];
						$rows[$row_key][$table_columns[$i]] .= "&idfld=" . $table_columns[0];
						$rows[$row_key][$table_columns[$i]] .= "'>";
						$rows[$row_key][$table_columns[$i]] .= number_format(strlen($row_sql[$table_columns[$i]]) / 1024, 2) . " Kb.";
						$rows[$row_key][$table_columns[$i]] .= "</a>";
				}
		}

        }
    }    
    
    $queryData = new queryData();
    $queryData->start = $start;
    $queryData->recordsTotal = $recordsTotal;
    $queryData->recordsFiltered = $recordsTotal;
    $queryData->data = $rows;
    
    return new Symfony\Component\HttpFoundation\Response(json_encode($queryData), 200);
});




/* Download blob img */
$app->match('/books/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . books . " WHERE ".$idfldname." = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($rowid));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('menu_list'));
    }

    header('Content-Description: File Transfer');
    header('Content-Type: image/jpeg');
    header("Content-length: ".strlen( $row_sql[$fieldname] ));
    header('Expires: 0');
    header('Cache-Control: public');
    header('Pragma: public');
    ob_clean();    
    echo $row_sql[$fieldname];
    exit();
   
    
});



$app->match('/books', function () use ($app) {
    
	$table_columns = array(
		'isbn', 
		'title', 
		'authorid', 
		'publishedate', 
		'edition', 
		'picurl', 
		'language', 
		'discribtion', 
		'price', 
		'localprice', 
		'tag', 
		'buycount', 
		'viewcount', 
		'categoryid', 
		'createdAt', 
		'updatedAt', 

    );

    $primary_key = "isbn";	

    return $app['twig']->render('books/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('books_list');



$app->match('/books/create', function () use ($app) {
    
    $initial_data = array(
		'isbn' => '', 
		'title' => '', 
		'authorid' => '', 
		'publishedate' => '', 
		'edition' => '', 
		'picurl' => '', 
		'language' => '', 
		'discribtion' => '', 
		'price' => '', 
		'localprice' => '', 
		'tag' => '', 
		'buycount' => '', 
		'viewcount' => '', 
		'categoryid' => '', 
		'createdAt' => '', 
		'updatedAt' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('isbn', 'text', array('required' => true));
	$form = $form->add('title', 'text', array('required' => true));
	$form = $form->add('authorid', 'text', array('required' => true));
	$form = $form->add('publishedate', 'text', array('required' => true));
	$form = $form->add('edition', 'text', array('required' => true));
	$form = $form->add('picurl', 'text', array('required' => false));
	$form = $form->add('language', 'text', array('required' => true));
	$form = $form->add('discribtion', 'textarea', array('required' => false));
	$form = $form->add('price', 'text', array('required' => true));
	$form = $form->add('localprice', 'text', array('required' => false));
	$form = $form->add('tag', 'text', array('required' => false));
	$form = $form->add('buycount', 'text', array('required' => true));
	$form = $form->add('viewcount', 'text', array('required' => true));
	$form = $form->add('categoryid', 'text', array('required' => true));
	$form = $form->add('createdAt', 'text', array('required' => true));
	$form = $form->add('updatedAt', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `books` (`isbn`, `title`, `authorid`, `publishedate`, `edition`, `picurl`, `language`, `discribtion`, `price`, `localprice`, `tag`, `buycount`, `viewcount`, `categoryid`, `createdAt`, `updatedAt`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['isbn'], $data['title'], $data['authorid'], $data['publishedate'], $data['edition'], $data['picurl'], $data['language'], $data['discribtion'], $data['price'], $data['localprice'], $data['tag'], $data['buycount'], $data['viewcount'], $data['categoryid'], $data['createdAt'], $data['updatedAt']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'books created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('books_list'));

        }
    }

    return $app['twig']->render('books/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('books_create');



$app->match('/books/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `books` WHERE `isbn` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('books_list'));
    }

    
    $initial_data = array(
		'isbn' => $row_sql['isbn'], 
		'title' => $row_sql['title'], 
		'authorid' => $row_sql['authorid'], 
		'publishedate' => $row_sql['publishedate'], 
		'edition' => $row_sql['edition'], 
		'picurl' => $row_sql['picurl'], 
		'language' => $row_sql['language'], 
		'discribtion' => $row_sql['discribtion'], 
		'price' => $row_sql['price'], 
		'localprice' => $row_sql['localprice'], 
		'tag' => $row_sql['tag'], 
		'buycount' => $row_sql['buycount'], 
		'viewcount' => $row_sql['viewcount'], 
		'categoryid' => $row_sql['categoryid'], 
		'createdAt' => $row_sql['createdAt'], 
		'updatedAt' => $row_sql['updatedAt'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('isbn', 'text', array('required' => true));
	$form = $form->add('title', 'text', array('required' => true));
	$form = $form->add('authorid', 'text', array('required' => true));
	$form = $form->add('publishedate', 'text', array('required' => true));
	$form = $form->add('edition', 'text', array('required' => true));
	$form = $form->add('picurl', 'text', array('required' => false));
	$form = $form->add('language', 'text', array('required' => true));
	$form = $form->add('discribtion', 'textarea', array('required' => false));
	$form = $form->add('price', 'text', array('required' => true));
	$form = $form->add('localprice', 'text', array('required' => false));
	$form = $form->add('tag', 'text', array('required' => false));
	$form = $form->add('buycount', 'text', array('required' => true));
	$form = $form->add('viewcount', 'text', array('required' => true));
	$form = $form->add('categoryid', 'text', array('required' => true));
	$form = $form->add('createdAt', 'text', array('required' => true));
	$form = $form->add('updatedAt', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `books` SET `isbn` = ?, `title` = ?, `authorid` = ?, `publishedate` = ?, `edition` = ?, `picurl` = ?, `language` = ?, `discribtion` = ?, `price` = ?, `localprice` = ?, `tag` = ?, `buycount` = ?, `viewcount` = ?, `categoryid` = ?, `createdAt` = ?, `updatedAt` = ? WHERE `isbn` = ?";
            $app['db']->executeUpdate($update_query, array($data['isbn'], $data['title'], $data['authorid'], $data['publishedate'], $data['edition'], $data['picurl'], $data['language'], $data['discribtion'], $data['price'], $data['localprice'], $data['tag'], $data['buycount'], $data['viewcount'], $data['categoryid'], $data['createdAt'], $data['updatedAt'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'books edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('books_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('books/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('books_edit');


$app->match('/books/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `books` WHERE `isbn` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `books` WHERE `isbn` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'books deleted!',
            )
        );
    }
    else{
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );  
    }

    return $app->redirect($app['url_generator']->generate('books_list'));

})
->bind('books_delete');



$app->match('/books/downloadList', function (Symfony\Component\HttpFoundation\Request $request) use($app){
    
    $table_columns = array(
		'isbn', 
		'title', 
		'authorid', 
		'publishedate', 
		'edition', 
		'picurl', 
		'language', 
		'discribtion', 
		'price', 
		'localprice', 
		'tag', 
		'buycount', 
		'viewcount', 
		'categoryid', 
		'createdAt', 
		'updatedAt', 

    );
    
    $table_columns_type = array(
		'varchar(150)', 
		'varchar(255)', 
		'int', 
		'datetime', 
		'varchar(255)', 
		'varchar(255)', 
		'varchar(255)', 
		'longtext', 
		'int', 
		'int', 
		'json', 
		'int', 
		'int', 
		'int', 
		'datetime', 
		'datetime', 

    );   

    $types_to_cut = array('blob');
    $index_of_types_to_cut = array();
    foreach ($table_columns_type as $key => $value) {
        if(in_array($value, $types_to_cut)){
            unset($table_columns[$key]);
        }
    }

    $columns_to_select = implode(',', array_map(function ($row){
        return '`'.$row.'`';
    }, $table_columns));
     
    $find_sql = "SELECT ".$columns_to_select." FROM `books`";
    $rows_sql = $app['db']->fetchAll($find_sql, array());
  
    $mpdf = new mPDF();

    $stylesheet = file_get_contents('../web/resources/css/bootstrap.min.css'); // external css
    $mpdf->WriteHTML($stylesheet,1);
    $mpdf->WriteHTML('.table {
    border-radius: 5px;
    width: 100%;
    margin: 0px auto;
    float: none;
}',1);

    $mpdf->WriteHTML(build_table($rows_sql));
    $mpdf->Output();
})->bind('books_downloadList');



