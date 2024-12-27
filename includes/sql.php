<?php
  require_once('includes/load.php');

/*--------------------------------------------------------------*/
/* Function for find all database table rows by table name
/*--------------------------------------------------------------*/
function find_all($table) {
   global $db;
   if(tableExists($table))
   {
     // "SELECT * FROM ".$db->escape($table)" is fine for Postgres
     return find_by_sql("SELECT * FROM ".$db->escape($table));
   }
}
/*--------------------------------------------------------------*/
/* Function for Perform queries
/*--------------------------------------------------------------*/
function find_by_sql($sql)
{
  global $db;
  $result = $db->query($sql);
  $result_set = $db->while_loop($result);
  return $result_set;
}
/*--------------------------------------------------------------*/
/* Function for Find data from table by id
/*--------------------------------------------------------------*/
function find_by_id($table,$id)
{
  global $db;
  $id = (int)$id;
  if(tableExists($table)){
    $sql  = "SELECT * FROM {$db->escape($table)}";
    $sql .= " WHERE id='{$db->escape($id)}' ";
    $result = $db->query($sql);
    if($row = $db->fetch_assoc($result))
      return $row;
    else
      return null;
  }
}
/*--------------------------------------------------------------*/
/* Function for Delete data from table by id
/*--------------------------------------------------------------*/
function delete_by_id($table,$id)
{
  global $db;
  if(tableExists($table))
  {
    $sql  = "DELETE FROM ".$db->escape($table);
    $sql .= " WHERE id=". $db->escape($id);
    $db->query($sql);
    return ($db->affected_rows() === 1 ? true : false);
  }
}
/*--------------------------------------------------------------*/
/* Function for Count id By table name
/*--------------------------------------------------------------*/
function count_by_id($table){
  global $db;
  if(tableExists($table))
  {
    $sql    = "SELECT COUNT(id) AS total FROM ".$db->escape($table);
    $result = $db->query($sql);
    return $db->fetch_assoc($result);
  }
}
/*--------------------------------------------------------------*/
/* Determine if database table exists in PostgreSQL
/* We replace the MySQL "SHOW TABLES" approach.
/*--------------------------------------------------------------*/
function tableExists($table){
  global $db;
  $escapedTable = $db->escape($table);

  // We'll query information_schema or use pg_catalog
  $sql  = "SELECT EXISTS (";
  $sql .= "  SELECT FROM information_schema.tables ";
  $sql .= "  WHERE table_name = '{$escapedTable}'";
  $sql .= ") AS table_exists";

  $result = $db->query($sql);
  if($row = $db->fetch_assoc($result)) {
    // 't' is true in PG. Could also get a boolean true/false.
    return ($row['table_exists'] == 't' || $row['table_exists'] == true);
  }
  return false;
}
/*--------------------------------------------------------------*/
/* Login with the data provided in $_POST, from login form.
/*--------------------------------------------------------------*/
function authenticate($username='', $password='') {
  global $db;
  $username = $db->escape($username);
  $password = $db->escape($password);

  $sql  = "SELECT id,username,password,user_level FROM users ";
  $sql .= "WHERE username ='{$username}' ";
  $result = $db->query($sql);

  if($db->num_rows($result)){
    $user = $db->fetch_assoc($result);
    $password_request = sha1($password);
    if($password_request === $user['password'] ){
      return $user['id'];
    }
  }
  return false;
}
/*--------------------------------------------------------------*/
/* authenticate_v2
/*--------------------------------------------------------------*/
function authenticate_v2($username='', $password='') {
  global $db;
  $username = $db->escape($username);
  $password = $db->escape($password);

  $sql  = "SELECT id,username,password,user_level FROM users ";
  $sql .= "WHERE username ='{$username}' LIMIT 1";
  $result = $db->query($sql);

  if($db->num_rows($result)){
    $user = $db->fetch_assoc($result);
    $password_request = sha1($password);
    if($password_request === $user['password'] ){
      return $user;
    }
  }
  return false;
}
/*--------------------------------------------------------------*/
/* Find current log in user by session id
/*--------------------------------------------------------------*/
function current_user(){
  static $current_user;
  global $db;
  if(!$current_user){
    if(isset($_SESSION['user_id'])){
      $user_id = intval($_SESSION['user_id']);
      $current_user = find_by_id('users',$user_id);
    }
  }
  return $current_user;
}
/*--------------------------------------------------------------*/
/* Find all user by joining users table and user_groups table
/*--------------------------------------------------------------*/
function find_all_user(){
  global $db;
  $sql  = "SELECT u.id,u.name,u.username,u.user_level,u.status,u.last_login,";
  $sql .= "g.group_name ";
  $sql .= "FROM users u ";
  $sql .= "LEFT JOIN user_groups g ";
  $sql .= "ON g.group_level=u.user_level ";
  $sql .= "ORDER BY u.name ASC";
  return find_by_sql($sql);
}
/*--------------------------------------------------------------*/
/* Function to update the last log in of a user
/*--------------------------------------------------------------*/
function updateLastLogIn($user_id)
{
  global $db;
  $date = make_date();
  $sql  = "UPDATE users SET last_login='{$date}' WHERE id ='{$user_id}' ";
  $db->query($sql);
  return ($db->affected_rows() === 1 ? true : false);
}
/*--------------------------------------------------------------*/
/* Find group name
/*--------------------------------------------------------------*/
function find_by_groupName($val)
{
  global $db;
  $sql  = "SELECT group_name FROM user_groups ";
  $sql .= "WHERE group_name = '{$db->escape($val)}' LIMIT 1";
  $result = $db->query($sql);
  return($db->num_rows($result) === 0 ? true : false);
}
/*--------------------------------------------------------------*/
/* Find group level
/*--------------------------------------------------------------*/
function find_by_groupLevel($level)
{
  global $db;
  $sql  = "SELECT group_level, group_status FROM user_groups ";
  $sql .= "WHERE group_level = '{$db->escape($level)}' LIMIT 1";
  $result = $db->query($sql);
  if($db->num_rows($result)){
     return $db->fetch_assoc($result);
  }
  return null;
}
/*--------------------------------------------------------------*/
/* Function for checking which user level has access to page
/*--------------------------------------------------------------*/
function page_require_level($require_level){
  global $session;
  $current_user = current_user();
  $login_level  = find_by_groupLevel($current_user['user_level']);

  // if user not logged in
  if (!$session->isUserLoggedIn(true)):
    $session->msg('d','Please login...');
    redirect('index.php', false);

  // if Group status is deactivated
  elseif(isset($login_level['group_status']) && $login_level['group_status'] === '0'):
    $session->msg('d','This level user has been banned!');
    redirect('home.php',false);
  elseif($current_user['user_level'] <= (int)$require_level):
    return true;

  else:
    $session->msg("d", "Sorry! you dont have permission to view the page.");
    redirect('home.php', false);
  endif;
}
/*--------------------------------------------------------------*/
/* Join product with categories and media
/*--------------------------------------------------------------*/
function join_product_table(){
  global $db;
  $sql  = "SELECT p.id, p.name, p.quantity, p.buy_price, p.sale_price,";
  $sql .= " p.media_id, p.date, c.name AS categorie, m.file_name AS image";
  $sql .= " FROM products p";
  $sql .= " LEFT JOIN categories c ON c.id = p.categorie_id";
  $sql .= " LEFT JOIN media m ON m.id = p.media_id";
  $sql .= " ORDER BY p.id ASC";
  return find_by_sql($sql);
}
/*--------------------------------------------------------------*/
/* For auto-suggest: find product by title
/*--------------------------------------------------------------*/
function find_product_by_title($product_name){
  global $db;
  $p_name = remove_junk($db->escape($product_name));
  $sql = "SELECT name FROM products WHERE name ILIKE '%{$p_name}%' ";
  return find_by_sql($sql);
}
/*--------------------------------------------------------------*/
/* Find all product info by product title
/*--------------------------------------------------------------*/
function find_all_product_info_by_title($title){
  global $db;
  $sql  = "SELECT * FROM products WHERE name ='{$db->escape($title)}' ";
  return find_by_sql($sql);
}
/*--------------------------------------------------------------*/
/* Update product quantity
/*--------------------------------------------------------------*/
function update_product_qty($qty,$p_id){
  global $db;
  $qty = (int)$qty;
  $id  = (int)$p_id;
  $sql = "UPDATE products
  SET quantity = quantity - {$qty}
    WHERE id = {$p_id}";
  $db->query($sql);
  return ($db->affected_rows() === 1 ? true : false);
}
/*--------------------------------------------------------------*/
/* Find recently added products
/*--------------------------------------------------------------*/
function find_recent_product_added($limit){
  global $db;
  $sql   = " SELECT p.id,p.name,p.sale_price,p.media_id,p.date,c.name AS categorie,";
  $sql  .= " m.file_name AS image FROM products p";
  $sql  .= " LEFT JOIN categories c ON c.id = p.categorie_id";
  $sql  .= " LEFT JOIN media m ON m.id = p.media_id";
  $sql  .= " ORDER BY p.id DESC LIMIT ".$db->escape((int)$limit);
  
  return find_by_sql($sql);
}
/*--------------------------------------------------------------*/
/* Find highest selling product
/*--------------------------------------------------------------*/
function find_higest_saleing_product($limit){
  global $db;
  $sql  = "SELECT p.name, COUNT(s.product_id) AS totalsold, SUM(s.qty) AS totalqty";
  $sql .= " FROM sales s";
  $sql .= " LEFT JOIN products p ON p.id = s.product_id";
  $sql .= " GROUP BY s.product_id, p.name";
  $sql .= " ORDER BY SUM(s.qty) DESC LIMIT ".$db->escape((int)$limit);
  return $db->query($sql);
}
/*--------------------------------------------------------------*/
/* Find all sales
/*--------------------------------------------------------------*/
function find_all_sale(){
  global $db;
  $sql  = "SELECT s.id, s.qty, s.price, s.date, p.name";
  $sql .= " FROM sales s";
  $sql .= " LEFT JOIN products p ON s.product_id = p.id";
  $sql .= " ORDER BY s.date DESC";
  return find_by_sql($sql);
}
/*--------------------------------------------------------------*/
/* Find recent sales
/*--------------------------------------------------------------*/
function find_recent_sale_added($limit){
  global $db;
  $sql  = "SELECT s.id, s.qty, s.price, s.date, p.name";
  $sql .= " FROM sales s";
  $sql .= " LEFT JOIN products p ON s.product_id = p.id";
  $sql .= " ORDER BY s.date DESC LIMIT ".$db->escape((int)$limit);
  return find_by_sql($sql);
}
/*--------------------------------------------------------------*/
/* Generate sales report by two dates
/*--------------------------------------------------------------*/
function find_sale_by_dates($start_date, $end_date){
  global $db;

  $start_date = date("Y-m-d", strtotime($start_date));
  $end_date   = date("Y-m-d", strtotime($end_date));

  $sql  = "SELECT ";
  $sql .= "  s.date AS date, ";
  $sql .= "  p.name, ";
  $sql .= "  p.sale_price, ";
  $sql .= "  p.buy_price, ";
  $sql .= "  COUNT(s.product_id) AS total_records, ";
  $sql .= "  SUM(s.qty) AS total_sales, ";
  $sql .= "  SUM(p.sale_price * s.qty) AS total_saleing_price, ";
  $sql .= "  SUM(p.buy_price * s.qty) AS total_buying_price ";
  $sql .= "FROM sales s ";
  $sql .= "LEFT JOIN products p ON s.product_id = p.id ";
  $sql .= "WHERE s.date BETWEEN '{$start_date}' AND '{$end_date}' ";
  
  $sql .= "GROUP BY s.date, p.name, p.sale_price, p.buy_price ";
  $sql .= "ORDER BY s.date DESC";

 
  return find_by_sql($sql);
}

/*--------------------------------------------------------------*/
/* Generate daily sales report
/* MySQL: DATE_FORMAT(s.date, '%Y-%m-%e')
/* PG   : TO_CHAR(s.date, 'YYYY-MM-DD')
/*--------------------------------------------------------------*/
function dailySales($year,$month){
  global $db;

  $sql  = "SELECT s.qty, TO_CHAR(s.date, 'YYYY-MM-DD') AS date, p.name, ";
  $sql .= "SUM(p.sale_price * s.qty) AS total_saleing_price";
  $sql .= " FROM sales s";
  $sql .= " LEFT JOIN products p ON s.product_id = p.id";
  $sql .= " WHERE TO_CHAR(s.date, 'YYYY-MM') = '{$year}-{$month}'";
  $sql .= " GROUP BY TO_CHAR(s.date, 'YYYY-MM-DD'), s.qty, p.name, s.product_id, s.date";

  return find_by_sql($sql);
}
/*--------------------------------------------------------------*/
/* Generate monthly sales report
/* MySQL: DATE_FORMAT(s.date, '%Y') = '{$year}'
/* PG   : TO_CHAR(s.date, 'YYYY') = '{$year}'
/*--------------------------------------------------------------*/
function monthlySales($year){
  global $db;
  $sql  = "SELECT s.qty, TO_CHAR(s.date, 'YYYY-MM-DD') AS date, p.name,";
  $sql .= " SUM(p.sale_price * s.qty) AS total_saleing_price";
  $sql .= " FROM sales s";
  $sql .= " LEFT JOIN products p ON s.product_id = p.id";
  $sql .= " WHERE TO_CHAR(s.date, 'YYYY') = '{$year}'";
  $sql .= " GROUP BY TO_CHAR(s.date, 'YYYY-MM-DD'), s.qty, p.name, s.product_id, s.date";
  $sql .= " ORDER BY TO_CHAR(s.date, 'YYYY-MM-DD') ASC";
  return find_by_sql($sql);
}

?>
