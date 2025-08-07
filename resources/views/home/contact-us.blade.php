@extends('layouts.app')

@section('title', 'Contact-us')
@section('content')
  <section class="contact-section relative">
    <div class="container-cstm">
        
        <div class="row">
         <!--    <div class="col-12 col-md-5 mb-4">
                <div class="row">
                    <div class="col-12 col-md-12 mb-3">
                        <div class="contact-info-bx text-center">
                            <img src="{{asset('images/call.png')}}" class="shape-call swing" alt="call" />
                            <p class="f-30 semi-bold mb-0">Phone Number</p>  
                            <p class="f-16 mb-0"><a  class="grey text-decoration-none" href="tel:123456789">123456789</a></p>  
                        </div>
                    </div>
                    <div class="col-12 col-md-12 mb-3">
                        <div class="contact-info-bx text-center">
                            <img src="{{asset('images/email.png')}}" class="shape-call swing" alt="call" />
                            <p class="f-30 semi-bold mb-0">Email Address</p>  
                            <p class="f-16 mb-0"><a  class="grey text-decoration-none" href="mailto:test@gmail.com">test@gmail.com</a></p>  
                        </div>
                    </div>
                    <div class="col-12 col-md-12 mb-3">
                        <div class="contact-info-bx text-center">
                            <img src="{{asset('images/map.png')}}" class="shape-call swing" alt="call" />
                            <p class="f-30 semi-bold mb-0">Address</p>  
                            <p class="f-16 mb-0"><a  class="grey text-decoration-none" href="tel:123456789">35926 Zane Junction, Beaumont, Utah - 47953, Virgin Islands, British</a></p>  
                        </div>
                    </div>
                </div>
            </div> -->
            <div class="col-12 col-md-12 mb-4">
                 <div class="header contact-frm">
                            <h1>Get In Touch</h1>
                            <p>Got questions, feedback, or just want to say hi? We'd love to hear from you! Drop us a message and we'll get back to you ASAP.</p>
                        </div>

                        <div class="contact-form">
                            <h2 style="margin-bottom: 1.5rem; color: #333;">Send us a Message</h2>
                            <form id="contactForm" action="{{ route('contact-us') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="name">Name</label>
                                    <input type="text" id="name" name="name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="subject">I'm reaching out about...</label>
                                    <select id="subject" name="subject" required>
                                        <option value="">Select a topic</option>
                                        <option value="feedback">General Feedback</option>
                                        <option value="technical">Technical Issue/Bug Report</option>
                                        <option value="feature">Feature Request</option>
                                        <option value="account">Account Help</option>
                                        <option value="other">Something Else</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="message">Message</label>
                                    <textarea id="message" name="message" placeholder="Tell us what's on your mind..." required></textarea>
                                </div>
                                
                                <button type="submit" class="btn" id="submitBtn">Send Message</button>
                            </form>
                        </div>
            </div>
           
        </div>
       
    </div>
    <img src="{{asset('images/poly.svg')}}" class="shape-poly-top" alt="shape" />
    <img src="{{asset('images/poly1.svg')}}" class="shape-polygn-right" alt="shape" />
</section>
<script>
    document.getElementById('contactForm').addEventListener('submit', function() {
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerText = 'Submitting...';
    });
</script>

@endsection