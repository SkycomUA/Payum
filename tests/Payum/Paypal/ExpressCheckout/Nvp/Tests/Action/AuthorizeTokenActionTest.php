<?php
namespace Payum\Paypal\ExpressCheckout\Nvp\Tests\Action;

use Payum\Paypal\ExpressCheckout\Nvp\Payment;
use Payum\Request\RedirectUrlInteractiveRequest;
use Payum\Paypal\ExpressCheckout\Nvp\Action\AuthorizeTokenAction;
use Payum\Paypal\ExpressCheckout\Nvp\Request\AuthorizeTokenRequest;
use Payum\Paypal\ExpressCheckout\Nvp\PaymentInstruction;

class AuthorizeTokenActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeSubClassOfActionPaymentAware()
    {
        $rc = new \ReflectionClass('Payum\Paypal\ExpressCheckout\Nvp\Action\AuthorizeTokenAction');
        
        $this->assertTrue($rc->isSubclassOf('Payum\Paypal\ExpressCheckout\Nvp\Action\ActionPaymentAware'));
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArgument()   
    {
        new AuthorizeTokenAction();
    }

    /**
     * @test
     */
    public function shouldSupportAuthorizeTokenRequest()
    {
        $action = new AuthorizeTokenAction();
        
        $this->assertTrue($action->supports(new AuthorizeTokenRequest(new PaymentInstruction)));
    }

    /**
     * @test
     */
    public function shouldNotSupportAnythingNotAuthorizeTokenRequest()
    {
        $action = new AuthorizeTokenAction($this->createApiMock());

        $this->assertFalse($action->supports(new \stdClass()));
    }

    /**
     * @test
     * 
     * @expectedException \Payum\Exception\RequestNotSupportedException
     */
    public function throwIfNotSupportedRequestGivenAsArgumentForExecute()
    {
        $action = new AuthorizeTokenAction($this->createApiMock());

        $action->execute(new \stdClass());
    }

    /**
     * @test
     * 
     * @expectedException \Payum\Exception\LogicException
     * @expectedExceptionMessage The token must be set. Have you run SetExpressCheckoutAction?
     */
    public function throwIfInstructionNotHaveTokenSet()
    {
        $action = new AuthorizeTokenAction($this->createApiMock());

        $action->execute(new AuthorizeTokenRequest(new PaymentInstruction));
    }

    /**
     * @test
     */
    public function throwRedirectUrlRequestIfInstructionNotHavePayerIdSet()
    {
        $expectedToken = 'theAuthToken';
        $expectedRedirectUrl = 'theRedirectUrl';
        
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('getAuthorizeTokenUrl')
            ->with($expectedToken)
            ->will($this->returnValue($expectedRedirectUrl))
        ;
                
        $action = new AuthorizeTokenAction();
        $action->setPayment(new Payment($apiMock));

        $request = new AuthorizeTokenRequest(new PaymentInstruction);
        $request->getInstruction()->setToken($expectedToken);

        try {
            $action->execute($request);
        } catch (RedirectUrlInteractiveRequest $redirectUrlRequest) {
            $this->assertEquals($expectedRedirectUrl, $redirectUrlRequest->getUrl());
            
            return;
        }
        
        $this->fail('RedirectUrlInteractiveRequest exception was expected.');
    }

    /**
     * @test
     */
    public function shouldDoNothingIfUserAlreadyAuthorizedToken()
    {
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->never())
            ->method('getAuthorizeTokenUrl')
        ;
        
        $action = new AuthorizeTokenAction();
        $action->setPayment(new Payment($apiMock));

        $request = new AuthorizeTokenRequest(new PaymentInstruction);
        $request->getInstruction()->setToken('aToken');
        //payer id means that the user already authorize the token. 
        //Entered his login\passowrd and press enter at paypal side.
        $request->getInstruction()->setPayerid('aPayerId');

        $action->execute($request);
    }

    /**
     * @test
     */
    public function throwRedirectUrlRequestIfForceTrue()
    {
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('getAuthorizeTokenUrl')
        ;

        $action = new AuthorizeTokenAction();
        $action->setPayment(new Payment($apiMock));

        $request = new AuthorizeTokenRequest(new PaymentInstruction, $force = true);
        $request->getInstruction()->setToken('aToken');
        $request->getInstruction()->setPayerid('aPayerId');

        try {
            $action->execute($request);
        } catch (RedirectUrlInteractiveRequest $redirectUrlRequest) {
            return;
        }

        $this->fail('RedirectUrlInteractiveRequest exception was expected.');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Payum\Paypal\ExpressCheckout\Nvp\Api
     */
    protected function createApiMock()
    {
        return $this->getMock('Payum\Paypal\ExpressCheckout\Nvp\Api', array(), array(), '', false);
    }
}