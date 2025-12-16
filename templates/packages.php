<?php
/**
 * Template: Package Selection Display
 * File: templates/packages.php
 * Shortcode: [pz_license_packages]
 */

if (!defined('ABSPATH')) exit;

// FIXED: Direct URL construction without any functions
$site_url = get_site_url(); // This gets your exact site URL
$student_enroll_url = $site_url . '/?pz_enroll=student';
$school_enroll_url = $site_url . '/?pz_enroll=school';
?>

<div class="pz-packages-section">
    <div class="pz-packages-header">
        <h2>Choose Your Package</h2>
        <p>Select the package that best fits your needs</p>
    </div>
    
    <div class="pz-packages-container">
        
        <!-- Student Package -->
        <div class="pz-package-card pz-student-package">
            <div class="pz-package-badge">Most Popular</div>
            <div class="pz-package-header">
                <h3>Student Package</h3>
                <div class="pz-package-price">
                    <span class="currency">£</span>
                    <span class="amount">49.99</span>
                    <span class="period">/year</span>
                </div>
            </div>
            
            <div class="pz-package-features">
                <ul>
                    <li>
                        <svg class="check-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Access to all study materials
                    </li>
                    <li>
                        <svg class="check-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Brilliant Exam Notes (eBook)
                    </li>
                    <li>
                        <svg class="check-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Model answers & past papers
                    </li>
                    <li>
                        <svg class="check-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Personal student dashboard
                    </li>
                    <li>
                        <svg class="check-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        12 months access
                    </li>
                </ul>
            </div>
            
            <div class="pz-package-action">
                <a href="<?php echo esc_url($student_enroll_url); ?>" class="pz-enroll-btn pz-student-btn">
                    Enrol Now
                    <svg class="arrow-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </a>
            </div>
        </div>
        
        <!-- School Package -->
        <div class="pz-package-card pz-school-package">
            <div class="pz-package-badge pz-school-badge">Best Value</div>
            <div class="pz-package-header">
                <h3>School Licence</h3>
                <div class="pz-package-price">
                    <span class="currency">£</span>
                    <span class="amount">199</span>
                    <span class="period">/year</span>
                </div>
            </div>
            
            <div class="pz-package-features">
                <ul>
                    <li>
                        <svg class="check-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        All Student Package features
                    </li>
                    <li>
                        <svg class="check-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <strong>Unlimited students</strong>
                    </li>
                    <li>
                        <svg class="check-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <strong>Unlimited teachers</strong>
                    </li>
                    <li>
                        <svg class="check-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Teacher dashboard with analytics
                    </li>
                    <li>
                        <svg class="check-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Student progress tracking
                    </li>
                    <li>
                        <svg class="check-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Central school admin panel
                    </li>
                    <li>
                        <svg class="check-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Priority support
                    </li>
                </ul>
            </div>
            
            <div class="pz-package-action">
                <a href="<?php echo esc_url($school_enroll_url); ?>" class="pz-enroll-btn pz-school-btn">
                    Enrol Now
                    <svg class="arrow-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </a>
            </div>
        </div>
        
    </div>
    
    <div class="pz-packages-footer">
        <p>All packages include access to regularly updated materials and resources</p>
    </div>
</div>