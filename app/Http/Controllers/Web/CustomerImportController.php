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

    public function index()
    {
        return view('imports');
    }

    public function create()
    {
        return view('new-import');
    }
}
