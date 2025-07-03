<?php declare(strict_types=1);

namespace Act\NewsletterCheckout\Subscriber;

use Shopware\Core\Checkout\Cart\Order\CartConvertedEvent;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Psr\Log\LoggerInterface;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CheckoutConfirmSubscriber implements EventSubscriberInterface
{
    private const string NEWSLETTER_CHECKOUT_KEY = 'newsletterCheckout';

    public function __construct(
        private readonly EntityRepository $newsletterRecipientRepository,
        private readonly SystemConfigService $systemConfigService,
        private readonly NewsletterSubscribeRoute $newsletterSubscribeRoute,
        private readonly RequestStack $requestStack,
        private readonly mixed $salesChannelContextFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmPageLoaded',
            CartConvertedEvent::class => 'onCartConverted',
            CheckoutOrderPlacedEvent::class => 'onOrderPlaced',
        ];
    }

    public function onCheckoutConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        $isActive = $this->systemConfigService->getBool('ActNewsletterCheckout.config.active', $salesChannelId);
        
        if (!$isActive) {
            return;
        }
        
        $customer = $event->getSalesChannelContext()->getCustomer();
        if (!$customer) {
            return;
        }

        $page = $event->getPage();
        
        $isSubscribed = $this->isCustomerSubscribedToNewsletter(
            $customer->getEmail(),
            $event->getSalesChannelContext()
        );


        $page->addExtension(
            'newsletterCheckout',
            new ArrayStruct([
                'isSubscribed' => $isSubscribed,
                'showCheckbox' => !$isSubscribed,
            ])
        );
    }

    public function onCartConverted(CartConvertedEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $newsletterSubscribe = $request->request->get(self::NEWSLETTER_CHECKOUT_KEY);
        
        if ($newsletterSubscribe === '1') {
            $convertedCart = $event->getConvertedCart();
            $customFields = $convertedCart['customFields'] ?? [];
            $customFields[self::NEWSLETTER_CHECKOUT_KEY] = true;
            $convertedCart['customFields'] = $customFields;
            
            $event->setConvertedCart($convertedCart);
        }
    }

    public function onOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        $order = $event->getOrder();
        $customFields = $order->getCustomFields();
        
        if (!isset($customFields[self::NEWSLETTER_CHECKOUT_KEY]) || !$customFields[self::NEWSLETTER_CHECKOUT_KEY]) {
            return;
        }

        $orderCustomer = $order->getOrderCustomer();
        if (!$orderCustomer) {
            return;
        }

        $salesChannelId = $order->getSalesChannelId();
        
        // Check if this is a real registered customer or a guest
        $customer = $orderCustomer->getCustomer();
        $isRealCustomer = $customer && $customer->getGuest() === false;
        
        if ($isRealCustomer) {
            // For real registered customers, include customer context
            $salesChannelContext = $this->salesChannelContextFactory->create(
                '',
                $salesChannelId,
                [
                    SalesChannelContextService::CUSTOMER_ID => $orderCustomer->getCustomerId(),
                ]
            );
        } else {
            // For guest customers, create context WITHOUT customer to force guest behavior
            $salesChannelContext = $this->salesChannelContextFactory->create(
                '',
                $salesChannelId,
                []
            );
        }
        
        // Get the storefront URL from current request context
        $request = $this->requestStack->getCurrentRequest();
        $storefrontUrl = $request ? $request->attributes->get('sw-storefront-url') : '';
        
        $dataBag = new RequestDataBag([
            'email' => $orderCustomer->getEmail(),
            'option' => NewsletterSubscribeRoute::OPTION_SUBSCRIBE,
            'storefrontUrl' => $storefrontUrl,
            'salutationId' => $orderCustomer->getSalutationId(),
            'firstName' => $orderCustomer->getFirstName(),
            'lastName' => $orderCustomer->getLastName(),
        ]);

        try {
            $this->newsletterSubscribeRoute->subscribe(
                $dataBag,
                $salesChannelContext,
                true
            );
        } catch (\Throwable $e) {
            $this->logger->error('Newsletter subscription failed during checkout', [
                'email' => $orderCustomer->getEmail(),
                'orderId' => $order->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isCustomerSubscribedToNewsletter(string $email, SalesChannelContext $context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $context->getSalesChannelId()));
        $criteria->addFilter(new EqualsAnyFilter('status', ['direct', 'optIn']));

        $result = $this->newsletterRecipientRepository->search($criteria, $context->getContext());
        
        return $result->getTotal() > 0;
    }

}