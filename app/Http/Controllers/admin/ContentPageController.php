<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\{ContentPage,Contact};
use App\Models\ManagefAQ;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Traits\SendResponseTrait;

class ContentPageController extends Controller
{

    use SendResponseTrait;
    /**
     * functionName : contentPage
     * createdDate  : 13-06-2024
     * purpose      : get and update the content page detail
    */
    public function contentPageDetail(Request $request , $slug){
        try{
            if($request->isMethod('get')){
               $content_detail =  ContentPage::where('slug',$slug)->first();
                return view("admin.contentPage.update",compact('content_detail'));
            }elseif( $request->isMethod('post') ){
                $rules = [
                    'title'         => 'required|string|max:255',
                    'content'       => 'required',
                ];
                
                $validator = Validator::make($request->all(), $rules);
                
                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator)->withInput();
                }
                
               
               ContentPage::where('slug',$slug)->update([
                    'title'     => $request->title,
                    'content'     => $request->content,
               ]);

                return redirect()->back()->with('success',ucfirst(str_replace('-', ' ', $slug)).' '.config('constants.SUCCESS.UPDATE_DONE'));
            }
        }catch(\Exception $e){
            return redirect()->back()->with("error", $e->getMessage());
        }
    }
    /**End method add**/

    /**
     * functionName : contentPage
     * createdDate  : 02-09-2024
     * purpose      : get content page detail for web
    */
    public function contentPage($slug){
        try{
        
            if(in_array($slug,['privacy-and-policy','about-us','terms-and-conditions','delete-account-steps'])){
                $content_detail =  ContentPage::where('slug',$slug)->first();

                return view("admin.content-page",compact('content_detail'));
            }elseif($slug == 'FAQ'){
                $content_detail = ManagefAQ::where('status',1)->orderBy('id','desc')->get();

                return view("admin.content-page",compact('content_detail'));
            } else{
                return redirect()->back()->with("error", 'Not Found');
            }
            
            
        }catch(\Exception $e){
            return redirect()->back()->with("error", $e->getMessage());
        }
    }
    /**End method contentPage**/

    /**
     * functionName : storeContact
     * createdDate  : 15-04-2025
     * purpose      : send message through contact us
    */
   public function storeContact(Request $request)
{
    try {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        $contact = Contact::create($validated);

        if ($contact) {
            // --- Send User Auto-Reply ---
            $userTemplate = $this->getTemplateByName('Contact_submit');
            if ($userTemplate) {
                $userReplacements = ['{{$name}}', '{{$companyName}}', '{{YEAR}}'];
                $userWithValues = [
                    $contact->name,
                    config('app.name'),
                    date('Y'),
                ];

                $emailBody = str_replace($userReplacements, $userWithValues, $userTemplate->template);

                $emailData = $this->mailData(
                    $contact->email,
                    str_replace(['{{$companyName}}'], [config('app.name')], $userTemplate->subject),
                    $emailBody,
                    'Contact_Reply',
                    $userTemplate->id
                );

                $this->mailSend($emailData);
            }

            // --- Send Admin Notification ---
            $adminTemplate = $this->getTemplateByName('new_contact');
            if ($adminTemplate) {
                $adminReplacements = ['{{$name}}', '{{$email}}', '{{$subject}}', '{{$user_message}}', '{{$companyName}}', '{{YEAR}}'];
                $adminWithValues = [
                    $contact->name,
                    $contact->email,
                    $contact->subject ?? 'N/A',
                    $contact->message,
                    config('app.name'),
                    date('Y'),
                ];

                $adminEmailBody = str_replace($adminReplacements, $adminWithValues, $adminTemplate->template);

                $adminEmailData = $this->mailData(
                    'info@edupalz.com', // <- Make sure this is set in your .env
                    str_replace(['{{$companyName}}'], [config('app.name')], $adminTemplate->subject),
                    $adminEmailBody,
                    'Contact_Alert',
                    $adminTemplate->id
                );

                $this->mailSend($adminEmailData);
            }
        }

        return redirect()->back()->with('success', 'Thank you for reaching out. We will get back to you soon!');

    } catch (\Exception $e) {
        \Log::error('Contact form submission failed: ' . $e->getMessage());

        return redirect()->back()->with('error', 'Something went wrong. Please try again later.');
    }
}

     /**End method storeContact**/



}
