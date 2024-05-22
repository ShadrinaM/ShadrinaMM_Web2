<?php
header('Content-Type: text/html; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  $isStarted = session_start(); //начало сессии
  $messages = array(); //массив сообщений для пользователя

  //вывод ошибок из куков
  if (!empty($_COOKIE['DBERROR'])) {
    $messages[] = $_COOKIE['DBERROR'] . '<br><br>';
    setcookie('DBERROR', '', time() - 3600);
  }
  if (!empty($_COOKIE['AUTHERROR'])) {
    $messages[] = $_COOKIE['AUTHERROR'] . '<br><br>';
    setcookie('AUTHERROR', '', time() - 3600);
  }
  if (!empty($_COOKIE['save'])) {
    setcookie('save', '', 100000);
    $messages[] = 'Спасибо, результаты сохранены.';
    // Если в куках есть пароль, то выводим сообщение.
    if (!empty($_COOKIE['pass'])) {
      $messages[] = sprintf(
        'Вы можете войти с логином <strong>%s</strong> паролем <strong>%s</strong> для повторного входа.<br>',
        strip_tags($_COOKIE['login']),
        strip_tags($_COOKIE['pass'])
      );
    }
    setcookie('save', '', time() - 3600);
    setcookie('login', '', time() - 3600);
    setcookie('pass', '', time() - 3600);
  }

  //если куки пустые
  $hasErrors = false;
  $errors = array();
  $errors['fio'] = !empty($_COOKIE['fio_error']);
  $errors['phone'] = !empty($_COOKIE['phone_error']);
  $errors['mail'] = !empty($_COOKIE['mail_error']);
  $errors['birthdate'] = !empty($_COOKIE['birthdate_error']);
  $errors['pol'] = !empty($_COOKIE['pol_error']);
  $errors['langg'] = !empty($_COOKIE['langg_error']);
  $errors['biog'] = !empty($_COOKIE['biog_error']);
  $errors['V'] = !empty($_COOKIE['V_error']);

  if ($errors['fio']) {
    setcookie('fio_error', '', 100000);
    setcookie('fio_value', '', 100000);
    $messages[] = '<div class="error">Заполните имя.</div>';
    $hasErrors = true;
  }
  if ($errors['phone']) {
    setcookie('phone_error', '', 100000);
    setcookie('phone_value', '', 100000);
    $messages[] = '<div class="error">Заполните телефон.</div>';
    $hasErrors = true;
  }
  if ($errors['mail']) {
    setcookie('mail_error', '', 100000);
    setcookie('mail_value', '', 100000);
    $messages[] = '<div class="error">Заполните почту.</div>';
    $hasErrors = true;
  }
  if ($errors['birthdate']) {
    setcookie('birthdate_error', '', 100000);
    setcookie('birthdate_value', '', 100000);
    $messages[] = '<div class="error">Заполните дату рождения.</div>';
    $hasErrors = true;
  }
  if ($errors['pol']) {
    setcookie('pol_error', '', 100000);
    setcookie('pol_value', '', 100000);
    $messages[] = '<div class="error">Выберите пол.</div>';
    $hasErrors = true;
  }
  if ($errors['langg']) {
    setcookie('langg_error', '', 100000);
    setcookie('langg_value', '', 100000);
    $messages[] = '<div class="error">Что-то не так с языком программирования!.</div>';
    $hasErrors = true;
  }
  if ($errors['biog']) {
    setcookie('biog_error', '', 100000);
    setcookie('biog_value', '', 100000);
    $messages[] = '<div class="error">Заполните биографию.</div>';
    $hasErrors = true;
  }
  if ($errors['V']) {
    setcookie('V_error', '', 100000);
    setcookie('V_value', '', 100000);
    $messages[] = '<div class="error">Подтвердите согласие.</div>';
    $hasErrors = true;
  }


  $values = array(); // если куки не пустые то массив заполняется данными из куки, иначе ''
  $values['fio'] = empty($_COOKIE['fio_value']) ? '' : $_COOKIE['fio_value'];
  $values['phone'] = empty($_COOKIE['phone_value']) ? '' : $_COOKIE['phone_value'];
  $values['mail'] = empty($_COOKIE['mail_value']) ? '' : $_COOKIE['mail_value'];
  $values['birthdate'] = empty($_COOKIE['birthdate_value']) ? '' : $_COOKIE['birthdate_value'];
  $values['pol'] = empty($_COOKIE['pol_value']) ? '' : $_COOKIE['pol_value'];
  $values['langg'] = empty($_COOKIE['langg_value']) ? array() : unserialize($_COOKIE['langg_value']);
  $values['biog'] = empty($_COOKIE['biog_value']) ? '' : $_COOKIE['biog_value'];
  $values['V'] = empty($_COOKIE['V_value']) ? '' : $_COOKIE['V_value'];


  if ($isStarted && !empty($_COOKIE[session_name()]) && !empty($_SESSION['hasLogged']) && $_SESSION['hasLogged']) {
    include ('../Secret.php');
    $user = userr;
    $pass = passs;
    $db = new PDO(
      "mysql:host=localhost;dbname=$user",
      $user,
      $pass,
      [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    try {
      $select = "SELECT * FROM LogPerson WHERE login = ?"; //текстр запроса
      $result = $db->prepare($select); //подготовка запроса 
      $result->execute([$_SESSION['login']]); //подстановка значения в ?
      $row = $result->fetch(); //из результата запроса выбирает 1 строку и сохран в row 
      // выписывает из строки значения в values
      $formID = $row['id'];
      $values['fio'] = $row['fio'];
      $values['phone'] = $row['phone'];
      $values['mail'] = $row['mail'];
      $values['birthdate'] = $row['birthdate'];
      $values['pol'] = $row['pol'];
      $values['biog'] = $row['biog'];
      $select = "SELECT id_l FROM person_and_lang WHERE id_u = ?";
      $result = $db->prepare($select);
      $result->execute([$formID]);
      $list = array();
      while ($row = $result->fetch()) {
        $list[] = $row['id_l'];
      }
      $values['langg'] = $list;
    } catch (PDOException $e) {
      $messages[] = 'Ошибка при загрузке формы из базы данных:<br>' . $e->getMessage();
    }
    $messages[] = "Выполнен вход с логином: <strong>" . $_SESSION['login'] . '</strong><br>';
    $messages[] = '<a href="login.php?exit=1">Выход из аккаунта</a>'; // вывод ссылки для выхода
  }
  // если не вошел, то вывести ссылку для входа
  elseif ($isStarted && !empty($_COOKIE[session_name()])) {
    $messages[] = '<a href="login.php">Войти в аккаунт</a><br>.';
  }

  include ('form.php');

} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {

  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF attack detected');
  }

  include ('../Secret.php');
  $user = userr;
  $pass = passs;
  $db = new PDO(
    "mysql:host=localhost;dbname=$user",
    $user,
    $pass,
    [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );

  /*
    В коде уже присутствует фильтрация входных данных с помощью функции preg_match. 
    Можно применить функцию htmlspecialchars, она преобразует специальные символы в HTML-сущности.
    $errors = FALSE;
    if (empty($_POST['fio']) || !preg_match('/^[а-яА-ЯёЁa-zA-Z\s-]{1,150}$/u', $_POST['fio'])) {
      setcookie('fio_error', '1', time() + 24 * 60 * 60);
      $errors = TRUE;
    } else {
      setcookie('fio_value', $_POST['fio'], time() + 30 * 24 * 60 * 60);
    }
  */

  $errors = FALSE;
  if (empty($_POST['fio']) || !preg_match('/^[а-яА-ЯёЁa-zA-Z\s-]{1,150}$/u', $_POST['fio'])) {
    setcookie('fio_error', '1', time() + 24 * 60 * 60);
    $errors = TRUE;
  } else {
    $fio = htmlspecialchars($_POST['fio'], ENT_QUOTES, 'UTF-8');
    setcookie('fio_value', $fio, time() + 30 * 24 * 60 * 60);
  }


  /*if (empty($_POST['phone']) || !preg_match('/^\+[0-9]{11}$/', $_POST['phone'])) {
    setcookie('phone_error', '1', time() + 24 * 60 * 60);
    $errors = TRUE;
  } else {
    setcookie('phone_value', $_POST['phone'], time() + 30 * 24 * 60 * 60);
  }*/

  if (empty($_POST['phone']) || !preg_match('/^\+[0-9]{11}$/', $_POST['phone'])) {
    setcookie('phone_error', '1', time() + 24 * 60 * 60);
    $errors = TRUE;
  } else {
    $phone = htmlspecialchars($_POST['phone'], ENT_QUOTES, 'UTF-8');
    setcookie('phone_value', $phone, time() + 30 * 24 * 60 * 60);
  }

  /*if (empty($_POST['mail']) || !preg_match('/^([a-z0-9_-]+(?:[-_.]?[a-z0-9]+)?@[a-z0-9_.-]+(?:\.?[a-z0-9]+)?\.[a-z]{2,5})$/i', $_POST['mail'])) {
    setcookie('mail_error', '1', time() + 24 * 60 * 60);
    $errors = TRUE;
  } else {
    setcookie('mail_value', $_POST['mail'], time() + 30 * 24 * 60 * 60);
  }*/

  if (empty($_POST['mail']) || !preg_match('/^([a-z0-9_-]+(?:[-_.]?[a-z0-9]+)?@[a-z0-9_.-]+(?:\.?[a-z0-9]+)?\.[a-z]{2,5})$/i', $_POST['mail'])) {
    setcookie('mail_error', '1', time() + 24 * 60 * 60);
    $errors = TRUE;
  } else {
    $mail = htmlspecialchars($_POST['mail'], ENT_QUOTES, 'UTF-8');
    setcookie('mail_value', $mail, time() + 30 * 24 * 60 * 60);
  }

  /*
  Приведение входных данных к типу с помощью функции strtotime, чтобы убедиться, что они являются валидной датой.
  if (empty($_POST['birthdate'])) {
    setcookie('birthdate_error', '1', time() + 24 * 60 * 60);
    $errors = TRUE;
  } else {
    setcookie('birthdate_value', $_POST['birthdate'], time() + 30 * 24 * 60 * 60);
  }
  */

  if (empty($_POST['birthdate'])) {
    setcookie('birthdate_error', '1', time() + 24 * 60 * 60);
    $errors = TRUE;
  } else {
    $birthdate = strtotime($_POST['birthdate']);
    if ($birthdate === false) {
      setcookie('birthdate_error', '1', time() + 24 * 60 * 60);
      $errors = TRUE;
    } else {
      setcookie('birthdate_value', $_POST['birthdate'], time() + 30 * 24 * 60 * 60);
    }
  }


  /*$polCheck = $_POST['pol'] == "1" || $_POST['pol'] == "2" || $_POST['pol'] == "3";
  if (empty($_POST['pol']) || !$polCheck) {
    setcookie('pol_error', '1', time() + 24 * 60 * 60);
    $errors = TRUE;
  } else {
    setcookie('pol_value', $_POST['pol'], time() + 30 * 24 * 60 * 60);
  }
  htmlspecialchars избыточен, так как входные данные используются только для сохранения в куки и не отображаются на странице.
  Функция is_numeric применяется к входным данным $_POST['pol'] для проверки, что они являются числом, 
  а функция intval применяется для преобразования их в целое число. 
  Это гарантирует, что входные данные являются валидным значением пола.
  
        <strong>
          Пол:
      </strong>
      <label>
        <input type="radio" name="pol" required value="1" <?php if (intval($values['pol'])===1) {print 'checked';}?> >
        Мужской
      </label>
      <label>
        <input type="radio" name="pol" required value="2"  <?php if (intval($values['pol'])===2) {print 'checked';}?>  >
        Женский
      </label>
      <label>
        <input type="radio" name="pol" required value="3" <?php if (intval($values['pol'])===3) {print 'checked';}?> >
        Ламинат
      </label>
      
  */

  $polCheck = is_numeric($_POST['pol']) && $_POST['pol'] >= 1 && $_POST['pol'] <= 3;
  if (empty($_POST['pol']) || !$polCheck) {
    setcookie('pol_error', '1', time() + 24 * 60 * 60);
    $errors = TRUE;
  } else {
    $pol = intval($_POST['pol']);
    setcookie('pol_value', $pol, time() + 30 * 24 * 60 * 60);
  }

  /*if (empty($_POST['langg'])) {
    setcookie('langg_error', '1', time() + 24 * 60 * 60);
    $errors = TRUE;
  } else {
    $sth = $db->prepare("SELECT id FROM Lang");
    $sth->execute();
    $langs = $sth->fetchAll();
    $has_incorrect_lang = false;
    foreach ($_POST['langg'] as $lang) {
      $flag = true;
      foreach ($langs as $index)
        if ($index[0] == $lang) {
          $flag = false;
          break;
        }
      if ($flag == true) {
        $has_incorrect_lang = true;
        $errors = true;
        break;
      }
    }
    if (!$has_incorrect_lang) {
      setcookie('langg_value', serialize($_POST['langg']), time() + 30 * 24 * 60 * 60);
    }
  }
  
  
  
  
  
  
  <strong>
    Любимый язык программирования:
  </strong>
  <div class="language">
      <label>
          <select name="langg[]" multiple="multiple"  <?php if ($errors['langg']) {print 'class="error"';}?>  >
              <option value="1" <?php if (in_array(1, $values['langg'])) { print 'selected'; } ?> > С </option>
              <option value="2" <?php if (in_array(2, $values['langg'])) { print 'selected'; } ?> > Pascal </option>
              <option value="3" <?php if (in_array(3, $values['langg'])) { print 'selected'; } ?> > Scala </option>
              <option value="4" <?php if (in_array(4, $values['langg'])) { print 'selected'; } ?> > C++ </option>
              <option value="5" <?php if (in_array(5, $values['langg'])) { print 'selected'; } ?> > Java </option>
              <option value="6" <?php if (in_array(6, $values['langg'])) { print 'selected'; } ?> > Python </option>
              <option value="7" <?php if (in_array(7, $values['langg'])) { print 'selected'; } ?> > JavaScript </option>
              <option value="8" <?php if (in_array(8, $values['langg'])) { print 'selected'; } ?> > PHP </option>
              <option value="9" <?php if (in_array(9, $values['langg'])) { print 'selected'; } ?> > Hascel </option>
              <option value="10" <?php if (in_array(10, $values['langg'])) { print 'selected'; } ?> > Clojure </option>
              <option value="11" <?php if (in_array(11, $values['langg'])) { print 'selected'; } ?> > Prolog </option>
          </select>
      </label>
  </div>
  */


  if (empty($_POST['langg'])) {
    setcookie('langg_error', '1', time() + 24 * 60 * 60);
    $errors = TRUE;
  } else {
    $sth = $db->prepare("SELECT id FROM Lang");
    $sth->execute();
    $langs = $sth->fetchAll();
    $has_incorrect_lang = false;
    $filtered_langs = array();
    foreach ($_POST['langg'] as $lang) {
      $lang = intval($lang);
      $flag = true;
      foreach ($langs as $index)
        if ($index[0] == $lang) {
          $flag = false;
          break;
        }
      if ($flag == true) {
        $has_incorrect_lang = true;
        $errors = true;
        break;
      } else {
        $filtered_langs[] = $lang;
      }
    }
    if (!$has_incorrect_lang) {
      setcookie('langg_value', serialize($filtered_langs), time() + 30 * 24 * 60 * 60);
    }
  }





  /*if (empty($_POST['biog'])) {
    setcookie('biog_error', '1', time() + 24 * 60 * 60);
    $errors = TRUE;
  } else {
    setcookie('biog_value', $_POST['biog'], time() + 30 * 24 * 60 * 60);
  }


  if (empty($_POST['V'])) {
    setcookie('V_error', '1', time() + 24 * 60 * 60);
    $errors = TRUE;
  } else {
    setcookie('V_value', $_POST['V'], time() + 30 * 24 * 60 * 60);
  }



  Функция strip_tags применяется к входным данным $_POST['biog'] и $_POST['V'] перед тем, как они сохраняются в куки. 
  Это гарантирует, что все HTML-теги, атрибуты и PHP-код будут удалены из входных данных.
*/



  if (empty($_POST['biog'])) {
    setcookie('biog_error', '1', time() + 24 * 60 * 60);
    $errors = TRUE;
  } else {
    setcookie('biog_value', strip_tags($_POST['biog']), time() + 30 * 24 * 60 * 60);
  }

  if (empty($_POST['V'])) {
    setcookie('V_error', '1', time() + 24 * 60 * 60);
    $errors = TRUE;
  } else {
    setcookie('V_value', strip_tags($_POST['V']), time() + 30 * 24 * 60 * 60);
  }





  if ($errors) {
    header('Location: index.php'); //если есть ошибки перезагружаем
    exit();
  } else {
    setcookie('fio_error', '', -10000); //удалемя куки ошибок
    setcookie('phone_error', '', -10000);
    setcookie('mail_error', '', -10000);
    setcookie('birthdate_error', '', -10000);
    setcookie('pol_error', '', -10000);
    setcookie('langg_error', '', -10000);
    setcookie('biog_error', '', -10000);
    setcookie('V_error', '', -10000);
  }

  $isStarted = session_start();
  if ($isStarted) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  if ($isStarted && !empty($_COOKIE[session_name()]) && !empty($_SESSION['hasLogged'])) {
    // перезапись данных в бд
    try {
      // получаем форму для данного логина
      $login = $_SESSION['login'];
      $select = "SELECT f.id FROM LogPerson f, Logi l WHERE l.login = '$login' AND f.login = l.login";
      $result = $db->query($select);
      $row = $result->fetch();
      $formID = $row['id'];
      // изменение данных в форме
      $updateForm = "UPDATE LogPerson SET fio = ?, phone = ?, mail = ?, birthdate = ?, pol = ?, biog = ? WHERE id = '$formID'";
      $formReq = $db->prepare($updateForm);
      $formReq->execute([$_POST['fio'], $_POST['phone'], $_POST['mail'], $_POST['birthdate'], $_POST['pol'], $_POST['biog']]);
      // удаляем прошлые языки
      $deleteLangs = "DELETE FROM person_and_lang WHERE id = '$formID'";
      $delReq = $db->query($deleteLangs);
      // заполняем заново языки
      $lang = "SELECT id FROM Lang WHERE id = ?";
      $feed = "INSERT INTO person_and_lang (id_u, id_l) VALUES (?, ?)";
      $langPrep = $db->prepare($lang);
      $feedPrep = $db->prepare($feed);
      foreach ($_POST['langg'] as $selection) {
        $langPrep->execute([$selection]);
        $langID = $langPrep->fetchColumn();
        $feedPrep->execute([$formID, $langID]);
      }
    } catch (PDOException $e) {
      setcookie('DBERROR', 'Error : ' . $e->getMessage());
      exit();
    }
  } else {
    // генерируем логин и пароль
    $login = substr(uniqid(), 3);
    $pass = rand(1000000, 9999999);
    // сохраняем в куки
    setcookie('login', $login);
    setcookie('pass', $pass);
    $_SESSION['hasLogged'] = false;

    try {
      $newUser = "INSERT INTO Logi (login, password) VALUES (?, ?)";
      $request = $db->prepare($newUser);
      $request->execute([$login, md5($pass)]); // сохранил логин и хеш пароля
      //добавляем данные формы нового пользователя  в бд
      $newForm = "INSERT INTO LogPerson (login, fio, phone, mail, birthdate, pol, biog) VALUES (?, ?, ?, ?, ?, ?, ?)";
      $formReq = $db->prepare($newForm);
      $formReq->execute([$login, $_POST['fio'], $_POST['phone'], $_POST['mail'], $_POST['birthdate'], $_POST['pol'], $_POST['biog']]);
      $userID = $db->lastInsertId();
      //и заполняет языки
      $lang = "SELECT id FROM Lang WHERE id = ?";
      $feed = "INSERT INTO person_and_lang (id_u, id_l) VALUES (?, ?)";
      $langPrep = $db->prepare($lang);
      $feedPrep = $db->prepare($feed);
      foreach ($_POST['selections'] as $selection) {
        $langPrep->execute([$selection]);
        $langID = $langPrep->fetchColumn();
        $feedPrep->execute([$userID, $langID]);
      }
    } catch (PDOException $e) {
      setcookie('DBERROR', 'Error : ' . $e->getMessage());
      exit();
    }
  }

  setcookie('save', '1');//сохранили куку о сохранении
  header('Location: index.php'); //перезагрузка

}
?>