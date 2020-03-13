<?php

use UmiCms\Service;

/** Класс пользовательских методов административной панели */
class CommentsCustomMacros
{

    /** @var comments $module */
    public $module;

    public function commentMail(iUmiEventPoint $event)
    {
        if ($event->getMode() != 'after') return;

        // необходимые параметры
        $iCommentId = $event->getParam('message_id');

        // получение объекта "комментарий"
        $comment = umiHierarchy::getInstance()->getElement($iCommentId);

        if ($comment instanceof umiHierarchyElement) {
            $message = array();
            // данные по комментарию
            $pageName = $comment->getName();
            $message['message'] = $comment->getValue('message');
            $message['url'] = $comment->getValue('url');
            $message['author_id'] = $comment->getValue('author_id');
            $author = umiObjectsCollection::getInstance()->getObject($message['author_id']);


            $auth = Service::Auth();
            if (!$auth->isAuthorized()) {
                $message['author_nick'] = $author->getValue('nickname');
                $message['author_email'] = $author->getValue('email');
                $message['author_ip'] = $author->getValue('ip');
            } else {
                $author = umiObjectsCollection::getInstance()->getObject($auth->getUserId());
                $message['author_nick'] = $author->getValue('fname');
                $message['author_email'] = $author->getValue('e-mail');
                $message['author_ip'] = '127.0.0.1';
            }

            $parentId = $comment->getParentId();

            // путь до родителя
            $path = umiHierarchy::getInstance()->getPathById($parentId);

            // получение данных по домену
            $defaultDomain = domainsCollection::getInstance()->getDefaultDomain();
            $domain = $defaultDomain->getHost();
            $pref = $defaultDomain->isUsingSsl() ? 'https://' : 'http://';
            $absolutePath = $pref . $domain . $path;

            $recipient = 'anatoliy.v@unikaweb.ru';
            $subject = 'Новый комментарий';
            $from = 'UMI.CMS';
            $from_mail = regedit::getInstance()->getVal('//settings/email_from');

            // формирование текста письма
            $content = "<html lang='ru'>
                     <body>
                         <div>
                             <p> На сайте был добавлен новый комментарий к странице с названием: <a href='{$absolutePath}'>'{$pageName}'</a> </p>
                             <p> <b>Заголовок комментария:</b> {$pageName} </p>
                             <div>
                                 <p> <b>Данные автора комментария:</b> </p>
                                 <p> <b>Прозвище:</b> {$message['author_nick']} </p>
                                 <p> <b>E-mail адрес:</b> {$message['author_email']} </p>
                                 <p> <b>IP-адрес:</b> {$message['author_ip']} </p>
                             </div>
                             <p> <b>Текст комментария:</b> </p>
                             <p> {$message['message']} </p>
                         </div>
                     </body>
                 </html>";

            // формирование письма
            $oMail = new umiMail();
            $oMail->setFrom($from_mail, $from);
            $oMail->setSubject($subject);
            $oMail->setContent($content);
            $oMail->addRecipient($recipient);

            // отправка
            $oMail->commit();
            $oMail->send();
        }
    }
}
