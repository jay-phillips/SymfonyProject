<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Email\Mailer;
use App\Security\TokenGenerator;
use Symfony\Component\Mime\Email;

use Doctrine\Common\EventSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use ApiPlatform\Core\EventListener\EventPriorities;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;



class UserRegisterSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserPasswordEncoderInterface 
     */
    private $passwordEncoder;

     /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

     /**
     * @var Mailer
     */
    private $mailer;


    public function __construct(
        UserPasswordEncoderInterface $passwordEncoder,
        TokenGenerator $tokenGenerator,
        Mailer $mailer
        )
    {
       $this->passwordEncoder = $passwordEncoder;
       $this->tokenGenerator = $tokenGenerator;
       $this->mailer = $mailer;
    }
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * ['eventName' => 'methodName']
     *  * ['eventName' => ['methodName', $priority]]
     *  * ['eventName' => [['methodName1', $priority], ['methodName2']]]
     *
     * The code must not depend on runtime state as it will only be called at compile time.
     * All logic depending on runtime state must be put into the individual methods handling the events.
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW =>['userRegistered', EventPriorities::PRE_WRITE]
        ];
    }


    public function userRegistered(ViewEvent $event)
    {
        $user = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$user instanceof User ||  !in_array($method, [Request::METHOD_POST]))
        {
           return; 
        }

        // It is an User, we need to hash password here 
        $user->setPassword(
            $this->passwordEncoder->encodePassword($user, $user->getPassword())
        );

        $user->setConfirmationToken(

            $this->tokenGenerator->getRandomSecureToken()
        );

        // Send e-mail here 
        $this->mailer->sendConfirmationEmail($user);

    }


    

    

    
}

