<?php

namespace App\Http\Controllers\Web;

use App\Repositories\CustomerInterface;
use Illuminate\Http\Request;

class CustomerImportController extends Controller
{
    /**
     * @var CustomerInterface
     */
    private $customerRepository;

    public function __construct(CustomerInterface $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('imports');
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        return view('new-import');
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function customers()
    {
        return view('customers');
    }
}
