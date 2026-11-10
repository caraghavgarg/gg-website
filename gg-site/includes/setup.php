<?php
/**
 * Tools → GG Site Setup: one-click creation of pages, services, team,
 * due dates, categories and both menus. Safe to run again: existing
 * services, team members and due dates are never duplicated, and the
 * previous content of any page it replaces is kept as a revision and in
 * the "_gg_previous_content" field.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', function () {
	add_management_page( 'GG Site Setup', 'GG Site Setup', 'manage_options', 'gg-site-setup', 'ggs_setup_page' );
} );

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'ggs_setup_done' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && 'tools_page_gg-site-setup' === $screen->id ) {
		return;
	}
	echo '<div class="notice notice-info"><p><strong>GG Site:</strong> run <a href="' . esc_url( admin_url( 'tools.php?page=gg-site-setup' ) ) . '">Tools → GG Site Setup</a> once to create the website pages, services and menus.</p></div>';
} );

function ggs_setup_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$log = array();
	if ( isset( $_POST['ggs_run_setup'] ) ) {
		check_admin_referer( 'ggs_setup' );
		$replace = ! empty( $_POST['ggs_replace_pages'] );
		$log     = ggs_run_setup( $replace );
	}
	$theme_ok = ( 'gg-theme' === get_template() );
	?>
	<div class="wrap">
		<h1>GG Site Setup</h1>
		<?php if ( ! $theme_ok ) : ?>
			<div class="notice notice-warning inline"><p>Activate the <strong>GG Theme</strong> (Appearance → Themes) before or after running setup. The menus are assigned to its two menu locations.</p></div>
		<?php endif; ?>
		<?php if ( $log ) : ?>
			<div class="notice notice-success inline"><p><strong>Setup finished.</strong></p><ul style="list-style:disc;margin-left:20px">
				<?php foreach ( $log as $line ) : ?><li><?php echo esc_html( $line ); ?></li><?php endforeach; ?>
			</ul><p><a class="button button-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank">View the website</a></p></div>
		<?php endif; ?>
		<p>This creates or updates:</p>
		<ul style="list-style:disc;margin-left:20px">
			<li>Pages: Home, About Us, Our Team, Services, Resources &amp; Publications, Contact Us, Calculators, Due Date Calendar, Acts &amp; Rules (with Income Tax, GST, MCA &amp; ROC, Labour Law and Others), Professional Resources.</li>
			<li>Eight services, three partner profiles and the standard due dates (skipped if any already exist).</li>
			<li>Post categories: Articles, Professional Updates, Notifications &amp; Circulars.</li>
			<li>Menus: <em>Main Menu</em>, <em>Utilities Toolbar</em>, <em>Resources Bar</em>, and two empty spare bars (<em>Extra Bar A</em>, <em>Extra Bar B</em>) – edit them in Appearance → Menus. Each page can choose which bars it shows.</li>
			<li>Sets Home as the front page and Resources &amp; Publications as the posts page; deactivates the older GG Knowledge Hub plugin.</li>
		</ul>
		<form method="post">
			<?php wp_nonce_field( 'ggs_setup' ); ?>
			<p><label><input type="checkbox" name="ggs_replace_pages" value="1" checked> Replace the content of existing pages with the same address (about, team, services, contact, calculators, home). Their old content is kept as a revision and can be restored from the page's Revisions screen.</label></p>
			<p><button type="submit" name="ggs_run_setup" value="1" class="button button-primary button-hero">Run setup</button></p>
		</form>
		<p class="description">Tip: take a backup from your hosting panel first. Running setup again later does not duplicate services, team members or due dates.</p>
	</div>
	<?php
}

/* ------------------------------------------------------------------ */

function ggs_block_p( $t ) {
	return "<!-- wp:paragraph -->\n<p>" . $t . "</p>\n<!-- /wp:paragraph -->\n\n";
}
function ggs_block_h( $t, $level = 2 ) {
	return '<!-- wp:heading' . ( 2 === $level ? '' : ' {"level":' . $level . '}' ) . " -->\n<h{$level} class=\"wp-block-heading\">" . $t . "</h{$level}>\n<!-- /wp:heading -->\n\n";
}
function ggs_block_list( $items, $class = '' ) {
	$attr = $class ? ' {"className":"' . $class . '"}' : '';
	$cls  = $class ? ' class="wp-block-list ' . $class . '"' : ' class="wp-block-list"';
	$out  = "<!-- wp:list{$attr} -->\n<ul{$cls}>";
	foreach ( $items as $i ) {
		$out .= "<!-- wp:list-item -->\n<li>" . $i . "</li>\n<!-- /wp:list-item -->";
	}
	return $out . "</ul>\n<!-- /wp:list -->\n\n";
}
function ggs_block_sc( $code ) {
	return "<!-- wp:shortcode -->\n" . $code . "\n<!-- /wp:shortcode -->\n\n";
}

function ggs_services_seed() {
	return array(
		array(
			'title' => 'Direct Taxation',
			'slug'  => 'direct-taxation',
			'tags'  => 'ITR Filing, Tax Planning, TDS/TCS, NRI & DTAA',
			'excerpt' => 'Income tax planning, return preparation and filing for individuals, HUFs, firms and companies, TDS/TCS compliance, and NRI taxation.',
			'body'  => ggs_block_p( 'We handle income tax for individuals, HUFs, firms, companies and trusts, from planning and return filing to assessments and refunds. Work for Tax Year 2026-27 onwards follows the Income-tax Act, 2025; earlier years continue under the Income-tax Act, 1961.' )
				. ggs_block_h( 'What we do' )
				. ggs_block_list( array( 'Tax planning and computation of income', 'Preparation and filing of income tax returns', 'TDS and TCS compliance: deduction, deposit and quarterly statements', 'Scrutiny assessments, reassessment, rectification and refund matters', 'NRI tax filing, DTAA advisory, foreign remittances and repatriation of funds' ) ),
		),
		array(
			'title' => 'GST',
			'slug'  => 'gst',
			'tags'  => 'GST Registration, GSTR Filing, ITC Reconciliation, GST Audit',
			'excerpt' => 'GST registration, monthly and quarterly returns, reconciliations, input tax credit, GST audits and representation before GST authorities.',
			'body'  => ggs_block_p( 'We manage GST compliance end to end for traders, manufacturers and service providers, and represent clients before GST authorities.' )
				. ggs_block_h( 'What we do' )
				. ggs_block_list( array( 'GST registration, amendment and cancellation', 'Monthly and quarterly returns: GSTR-1, GSTR-3B; annual returns GSTR-9 and GSTR-9C', 'Reconciliation of purchases with GSTR-2B and input tax credit review', 'GST audits', 'Replies to notices, and appeals before GST authorities' ) ),
		),
		array(
			'title' => 'Audit & Assurance',
			'slug'  => 'audit-assurance',
			'tags'  => 'Statutory Audit, Tax Audit, Bank Audit, Internal Audit',
			'excerpt' => 'Statutory, tax, bank, concurrent, internal and special-purpose audits conducted with independence and professional scepticism.',
			'body'  => ggs_block_p( 'Our audits are conducted with rigorous independence and professional scepticism. The firm is registered with RBI and CAG for government and banking audits.' )
				. ggs_block_h( 'What we do' )
				. ggs_block_list( array( 'Statutory audits of companies and other entities', 'Tax audits', 'Bank audits and concurrent audits', 'Internal audits', 'Special-purpose audits and certifications' ) ),
		),
		array(
			'title' => 'Litigation & Representation',
			'slug'  => 'litigation-representation',
			'tags'  => 'CIT(A), ITAT, GST Appeals, NCLT',
			'excerpt' => 'Representation before income tax and GST authorities, CIT(A), ITAT, NCLT and Debt Recovery Tribunals, and replies to departmental notices.',
			'body'  => ggs_block_p( 'We prepare replies and written submissions and appear for clients before tax authorities and tribunals.' )
				. ggs_block_h( 'What we do' )
				. ggs_block_list( array( 'Replies to income tax and GST notices', 'Representation in scrutiny and reassessment proceedings', 'Appeals before CIT(A) and ITAT', 'GST appeals and representation before GST authorities', 'Representation before NCLT and Debt Recovery Tribunals', 'IBC advisory and representation in NCLT proceedings' ) ),
		),
		array(
			'title' => 'Corporate Law & ROC Compliance',
			'slug'  => 'corporate-law-roc-compliance',
			'tags'  => 'Incorporation, ROC Filings, LLP, Secretarial Audit',
			'excerpt' => 'Company and LLP incorporation, ROC and MCA21 filings, board resolutions, statutory registers, secretarial audit, and business registrations.',
			'body'  => ggs_block_p( 'We set up and maintain companies and LLPs under the Companies Act, 2013 and the LLP Act, 2008, and handle the registrations a business needs to operate.' )
				. ggs_block_h( 'What we do' )
				. ggs_block_list( array( 'Incorporation of private limited companies, OPCs, LLPs and Section 8 companies', 'Annual ROC filings and event-based MCA21 filings', 'Board and shareholder resolutions, statutory registers and minutes', 'Secretarial audit', 'Winding-up and strike-off proceedings', 'Registrations: MSME/Udyam, FSSAI, IEC, trademark, PAN/TAN, ESI/PF, professional tax, shops and establishments' ) ),
		),
		array(
			'title' => 'Accounting & Bookkeeping',
			'slug'  => 'accounting-bookkeeping',
			'tags'  => 'Tally ERP, Payroll, MIS Reports, Financial Statements',
			'excerpt' => 'Outsourced accounting, Tally/ERP implementation, payroll, bank reconciliations, Schedule III financial statements and MIS reporting.',
			'body'  => ggs_block_p( 'We keep books accurate and up to date so that tax, GST and ROC filings follow without delay.' )
				. ggs_block_h( 'What we do' )
				. ggs_block_list( array( 'Outsourced accounting and bookkeeping', 'Tally / ERP implementation', 'Payroll processing', 'Bank reconciliations', 'Financial statements as per Schedule III of the Companies Act, 2013', 'MIS reporting' ) ),
		),
		array(
			'title' => 'Financial Advisory',
			'slug'  => 'financial-advisory',
			'tags'  => 'DPR & CMA Data, Loan Syndication, Valuation, FEMA',
			'excerpt' => 'Project reports and CMA data, loan syndication, valuation under the Insolvency and Bankruptcy Code, and FEMA advisory.',
			'body'  => ggs_block_p( 'We help businesses raise finance and take informed financial decisions.' )
				. ggs_block_h( 'What we do' )
				. ggs_block_list( array( 'Detailed project reports (DPR) and CMA data', 'Loan syndication: term loans, working capital, MSME loans and government-sponsored schemes', 'Liaison with banks and financial institutions', 'Valuation of securities or financial assets (SFA) for stressed assets under the IBC', 'FEMA compliance and advisory, including NRI investments in India' ) ),
		),
		array(
			'title' => 'Business Consultancy',
			'slug'  => 'business-consultancy',
			'tags'  => 'Due Diligence, Restructuring, Drafting, HR Advisory',
			'excerpt' => 'Business advisory, restructuring, due diligence and feasibility studies, drafting of business agreements, and HR and labour-law advisory.',
			'body'  => ggs_block_p( 'We advise businesses at every stage of growth, from feasibility to restructuring.' )
				. ggs_block_h( 'What we do' )
				. ggs_block_list( array( 'Business advisory and strategic financial planning', 'Financial restructuring and business valuation', 'Due diligence and feasibility studies', 'Drafting of contracts, partnership deeds, MOUs and agreements', 'HR policy drafting, salary structuring and manpower advisory', 'Labour-law compliance including PF, ESI and bonus' ) ),
		),
	);
}

function ggs_team_seed() {
	return array(
		array(
			'title' => 'CA Ashwani Kumar Garg',
			'designation' => 'Founder & Senior Partner',
			'qualifications' => 'FCA, LLB',
			'email' => 'mail@ggassociates.in',
			'bio' => 'CA Ashwani Kumar Garg qualified as a Chartered Accountant in 1984 and founded the practice in Kurukshetra. He founded Garg Garg & Associates in 2002. He served as Chairman of the Kurukshetra Branch of NIRC of ICAI (2018–19) and as Vice Chairman of the Karnal Branch. He holds a Bachelor of Laws (LLB) earned in 2019. His work centres on Direct Taxation, Statutory Audits and Corporate Law.',
		),
		array(
			'title' => 'CA Raghav Garg',
			'designation' => 'Partner – Garg Raghav & Associates',
			'qualifications' => 'FCA, LLM, LLB, DISA',
			'email' => 'raghav@gargraghav.in',
			'bio' => 'CA Raghav Garg qualified as a Chartered Accountant in 2009 and has over 15 years of experience in Income Tax, GST, Corporate Affairs and FEMA. He founded Garg Raghav & Associates in 2019 and is a Past Chairman of the Kurukshetra Branch of ICAI.',
		),
		array(
			'title' => 'CA Aditi Aggarwal',
			'designation' => 'Chartered Accountant & SFA Valuer',
			'qualifications' => 'CA, SFA Valuer (IBBI), CPA (US) – pursuing, BCom (KUK)',
			'email' => 'mail@ggassociates.in',
			'bio' => 'CA Aditi Aggarwal is a Chartered Accountant and a registered valuer (Securities or Financial Assets) with the Insolvency and Bankruptcy Board of India. She is pursuing the CPA (US) qualification and holds a BCom from Kurukshetra University. She works on valuation, corporate services and compliance.',
		),
	);
}

function ggs_duedates_seed() {
	$gst = 'https://www.gst.gov.in/';
	$it  = 'https://www.incometax.gov.in/iec/foportal/';
	return array(
		array( 'TDS / TCS payment', '7th', 7, 'Monthly', 'TDS/TCS', 'Tax deducted or collected in the previous month', $it ),
		array( 'GSTR-7 and GSTR-8', '10th', 10, 'Monthly', 'GST', 'TDS and TCS returns under GST', $gst ),
		array( 'GSTR-1 (monthly filers)', '11th', 11, 'Monthly', 'GST', 'Outward supplies of the previous month', $gst ),
		array( 'GSTR-6 (input service distributor)', '13th', 13, 'Monthly', 'GST', '', $gst ),
		array( 'GSTR-3B (monthly filers)', '20th', 20, 'Monthly', 'GST', 'Summary return and tax payment', $gst ),
		array( 'PMT-06 (QRMP scheme)', '25th', 25, 'Monthly', 'GST', 'Tax payment for the first two months of a quarter', $gst ),
		array( 'TDS statements', '31 Jul · 31 Oct · 31 Jan · 31 May', 31, 'Quarterly', 'TDS/TCS', 'Quarterly TDS returns for Q1 to Q4', $it ),
		array( 'Income tax return (non-audit cases)', '31 July', 0, 'Annual', 'Income Tax', 'Individuals and others not requiring audit', $it ),
		array( 'Tax audit report', '30 September', 0, 'Annual', 'Income Tax', 'Assessees requiring audit', $it ),
		array( 'Income tax return (audit cases)', '31 October', 0, 'Annual', 'Income Tax', 'Assessees requiring audit', $it ),
	);
}

function ggs_page_defs() {
	$p = 'ggs_block_p';
	$h = 'ggs_block_h';
	$l = 'ggs_block_list';
	return array(
		'home' => array( 'Home', $p( 'Garg Garg &amp; Associates is a Chartered Accountancy practice in Kurukshetra, Haryana. The practice began in 1984, when CA Ashwani Kumar Garg qualified as a Chartered Accountant, and was reconstituted as Garg Garg &amp; Associates (ICAI Reg. No. 018813N) in 2002.' ) . $p( 'Together with Garg Raghav &amp; Associates and GG Associates Private Limited, the group provides audit, taxation, GST, corporate law and advisory services to individuals, firms, companies and institutions.' ), '' ),
		'about' => array( 'About Us',
			$h( 'Our story' )
			. $p( 'CA Ashwani Kumar Garg qualified as a Chartered Accountant in 1984 and became Kurukshetra\'s pioneer CA. What began as a small practice under the name Galhotra Garg &amp; Company has served clients across Haryana and beyond for over 40 years.' )
			. $p( 'In 2002 the firm was reconstituted as Garg Garg &amp; Associates (ICAI Reg. No. 018813N). In 2019 CA Raghav Garg established the sister concern Garg Raghav &amp; Associates, focused on income tax, GST and corporate affairs. Together with GG Associates Private Limited, the group provides financial, legal and corporate services under one umbrella.' )
			. $h( 'Our journey' )
			. $l( array(
				'<strong>1984</strong> CA Ashwani Kumar Garg qualifies as a Chartered Accountant and establishes Kurukshetra\'s first CA practice.',
				'<strong>2002</strong> The firm is reconstituted as Garg Garg &amp; Associates, ICAI Registration No. 018813N.',
				'<strong>2010s</strong> Empanelled with the Reserve Bank of India (RBI) and the Comptroller &amp; Auditor General (CAG) for banking and government audits.',
				'<strong>2018–19</strong> CA Ashwani Kumar Garg serves as Chairman of the Kurukshetra Branch of NIRC of ICAI.',
				'<strong>2019</strong> Sister concern Garg Raghav &amp; Associates established under CA Raghav Garg.',
				'<strong>2024</strong> Forty years of practice.',
			), 'gg-timeline' )
			. $h( 'Our values' )
			. $l( array(
				'<strong>Integrity and ethics.</strong> Ethical standards in every engagement; a reputation built over 40 years of honest, transparent practice.',
				'<strong>Client first.</strong> Every solution is shaped around the client\'s business and long-term goals.',
				'<strong>Continuous learning.</strong> Keeping up with regulatory changes and tax amendments.',
				'<strong>Long relationships.</strong> Many clients who came to the firm in the 1980s are still with us, now alongside their children.',
			), 'gg-values' )
			. $h( 'Experience &amp; credentials' )
			. $l( array(
				'In practice since 1984; reconstituted as Garg Garg &amp; Associates in 2002',
				'ICAI firm registration no. 018813N',
				'Empanelled with RBI and CAG for bank and government audits',
				'Partners holding FCA, LLB, LLM and DISA qualifications; an IBBI-registered valuer (SFA)',
				'Head office at Sector 7, Urban Estate, Kurukshetra',
			) ), '' ),
		'team' => array( 'Our Team', $p( 'Our partners and professionals bring together accounting, taxation and law.' ), 'page-team.php' ),
		'services' => array( 'Our Services', $p( 'Since 2002, Garg Garg &amp; Associates has provided the full range of Chartered Accountancy services to individuals, firms, companies and institutions.' ), 'page-services.php' ),
		'resources' => array( 'Resources &amp; Publications', '', '' ),
		'contact' => array( 'Contact Us', $p( 'Visit us at our Kurukshetra office, call, or send a request below and we will get back to you.' ), 'page-contact.php' ),
		'calculators' => array( 'Calculators', $p( 'Quick calculators for common tax and finance questions.' ) . ggs_block_sc( '[gg_calculators]' ), '' ),
		'due-dates' => array( 'Due Date Calendar', $p( 'Regular statutory due dates under GST and income tax.' ) . ggs_block_sc( '[gg_due_dates]' ), '' ),
		'acts-rules' => array( 'Acts, Rules &amp; Notifications', $p( 'Bare Acts, Rules, forms and calculators for each area of law, linked to the official government sources.' ) . ggs_block_sc( '[gg_hub_index]' ), '' ),
		'income-tax' => array( 'Income Tax', ggs_block_sc( '[gg_hub head="income-tax"]' ), '' ),
		'gst' => array( 'GST', ggs_block_sc( '[gg_hub head="gst"]' ), '' ),
		'mca-roc' => array( 'MCA &amp; ROC', ggs_block_sc( '[gg_hub head="mca-roc"]' ), '' ),
		'finance' => array( 'Finance', ggs_block_sc( '[gg_hub head="finance"]' ), '' ),
		'labour-law' => array( 'Labour Law', ggs_block_sc( '[gg_hub head="labour-law"]' ), '' ),
		'others' => array( 'Other Laws', ggs_block_sc( '[gg_hub head="others"]' ), '' ),
		'our-group' => array( 'Our Group',
			$p( 'The GG group brings three practices together under one roof in Kurukshetra, so that audit, taxation, corporate law and business support can be handled by one team that already knows the client.' )
			. $h( 'Garg Garg &amp; Associates, Chartered Accountants' )
			. $p( 'The founding firm. CA Ashwani Kumar Garg began practice in 1984, and the firm was reconstituted as Garg Garg &amp; Associates in 2002 (ICAI Firm Registration No. 018813N). The firm is empanelled with the Reserve Bank of India and the Comptroller &amp; Auditor General of India for bank and government audits.' )
			. $l( array( 'Statutory, tax, bank and concurrent audits', 'Direct taxation and representation', 'Audits of trusts, societies and cooperative institutions' ) )
			. $h( 'Garg Raghav &amp; Associates, Chartered Accountants' )
			. $p( 'Established in 2019 by CA Raghav Garg (ICAI Firm Registration No. 036432N). The firm concentrates on income tax, GST, corporate affairs and FEMA, including notices, assessments, appeals and compliance for companies and their promoters.' )
			. $l( array( 'Income tax notices, assessments and appeals', 'GST registration, returns, audits and departmental proceedings', 'Company law, ROC and FEMA compliance' ) )
			. $h( 'GG Associates Private Limited' )
			. $p( 'CIN: U93000HR2013PTC050025. The group\'s company for financial, legal and corporate support services that sit alongside the professional work of the two firms, such as documentation, registrations and back-office support.' )
			. $h( 'One team for the client' )
			. $p( 'Each engagement is accepted and signed by the firm that is professionally responsible for it. Clients deal with one office and one contact, whichever entity carries out the work.' ), '' ),
		'careers' => array( 'Careers &amp; Articleship',
			$p( 'We train and work with people who want a thorough grounding in audit, taxation and corporate law. Our office is in Kurukshetra.' )
			. $h( 'Who we are looking for' )
			. $l( array(
				'<strong>CA Articleship.</strong> Students registered for articleship under the ICAI scheme. Articles work on statutory, tax and bank audits, income tax and GST returns, ROC filings and departmental matters, with direct guidance from the partners.',
				'<strong>Semi-qualified CA / CA Intermediate.</strong> Candidates with audit and tax experience who can take charge of client files.',
				'<strong>Qualified Chartered Accountants.</strong> For audit, GST, income tax litigation and corporate law work.',
				'<strong>Accounts / Tax Assistants.</strong> Graduates comfortable with Tally, Excel and the income tax and GST portals.',
			), 'gg-values' )
			. $h( 'What you get' )
			. $l( array( 'Exposure to a wide range of clients: companies, firms, trusts, societies, banks and individuals', 'Work on real departmental proceedings, not only compliance', 'Stipend for articled assistants as prescribed by ICAI; salary for staff according to experience', 'Time and support for CA examinations' ) )
			. $h( 'How to apply' )
			. $p( 'Fill in the form below and email your CV (PDF) to <a href="mailto:mail@ggassociates.in">mail@ggassociates.in</a> with the position in the subject line.' )
			. ggs_block_sc( '[gg_enquiry type="careers"]' ), '' ),
		'disclaimer' => array( 'Disclaimer',
			$p( 'Under the rules of the Institute of Chartered Accountants of India (ICAI), Chartered Accountants are not permitted to solicit work or advertise. By using this website you acknowledge that:' )
			. $l( array(
				'there has been no advertisement, personal communication, solicitation, invitation or inducement of any sort from Garg Garg &amp; Associates, Garg Raghav &amp; Associates, GG Associates Private Limited or any of their members to solicit work through this website;',
				'you wish to gain more information about the group for your own information and use;',
				'the information on this website is provided on request and is for general information only. It is not professional advice and does not create a client relationship;',
				'acts, rules, forms, due dates and calculators are provided for convenience. Laws, rates and due dates change and may be extended by notification, so please verify them from the official source or consult us before acting;',
				'links to government portals and other websites are given for convenience. We are not responsible for the content or availability of those websites.',
			) )
			. $p( 'The group accepts no liability for any loss or damage caused by reliance on the information on this website.' ), '' ),
		'privacy-policy' => array( 'Privacy Policy',
			$p( 'This policy explains what personal information this website collects and how Garg Garg &amp; Associates uses it. It applies only to this website, not to information we receive from clients in the course of an engagement, which is governed by our professional duty of confidentiality.' )
			. $h( 'What we collect' )
			. $l( array(
				'<strong>Enquiry and appointment requests:</strong> your name, phone number, email address, the service you are interested in, a preferred date and your message.',
				'<strong>Job applications:</strong> your name, contact details, the position and the information you give about yourself. CVs are received by email.',
				'<strong>Technical information:</strong> our web host records standard server logs (such as IP address and browser type) for security. The site uses only the cookies needed for it to work. The map on the Contact page is provided by Google, which may set its own cookies.',
			) )
			. $h( 'How we use it' )
			. $p( 'We use your details only to reply to your request, arrange an appointment or consider your application. We do not sell or share them with anyone for marketing. Requests are stored in the website\'s secure dashboard and sent to our office email.' )
			. $h( 'How long we keep it' )
			. $p( 'Enquiries are kept for up to two years and job applications for up to one year, unless you become a client or employee, in which case our normal record-keeping applies.' )
			. $h( 'Your rights' )
			. $p( 'Under the Digital Personal Data Protection Act, 2023 you may ask us to see, correct or delete the personal data you have given us through this website, or withdraw your consent. Write to <a href="mailto:mail@ggassociates.in">mail@ggassociates.in</a> and we will respond promptly.' )
			. $h( 'Please do not send' )
			. $p( 'Do not send PAN, Aadhaar, bank details, passwords or documents through the website forms. We will ask for documents through a secure channel when needed.' )
			. $h( 'Contact' )
			. $p( 'Garg Garg &amp; Associates, Chartered Accountants, Kothi No. 35, First Floor, Sector 7, Urban Estate, Kurukshetra, Haryana 136118 · <a href="mailto:mail@ggassociates.in">mail@ggassociates.in</a>' ), '' ),
		'terms-of-use' => array( 'Terms of Use',
			$p( 'By using this website you agree to these terms. If you do not agree, please do not use the website.' )
			. $h( 'Information only' )
			. $p( 'The content of this website, including acts, rules, forms, due dates, articles and calculators, is general information. It is not professional advice and should not be relied on without consulting a qualified professional about your circumstances. See also our <a href="/disclaimer/">Disclaimer</a>.' )
			. $h( 'Calculators' )
			. $p( 'Calculators give estimates based on simplified assumptions and the rates entered in them. Actual tax, interest or liability may differ.' )
			. $h( 'Links to other websites' )
			. $p( 'Links to government portals and other websites are provided for convenience. We do not control those websites and are not responsible for their content.' )
			. $h( 'Copyright' )
			. $p( 'The text, design and illustrations of this website belong to Garg Garg &amp; Associates unless stated otherwise. Bare acts, rules and forms belong to the respective government authorities. You may print or share pages for personal, non-commercial use with acknowledgement.' )
			. $h( 'Limitation of liability' )
			. $p( 'To the extent permitted by law, the group is not liable for any loss arising from the use of this website or reliance on its content.' )
			. $h( 'Governing law' )
			. $p( 'These terms are governed by the laws of India, and the courts at Kurukshetra, Haryana have jurisdiction.' ), '' ),
		'gst-registration-drafter' => array( 'GST Registration Drafter', ggs_block_sc( '[gg_tool name="gst-registration-drafter"]' ), '' ),
		'professional-resources' => array( 'Professional Resources', $p( 'Government portals and professional bodies we use every day.' ) . ggs_block_sc( '[gg_portal_links]' ), '' ),
	);
}

function ggs_run_setup( $replace ) {
	$log = array();

	// 1. Services.
	$existing = get_posts( array( 'post_type' => 'gg_service', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
	if ( ! $existing ) {
		foreach ( ggs_services_seed() as $i => $s ) {
			$id = wp_insert_post( array(
				'post_type'    => 'gg_service',
				'post_status'  => 'publish',
				'post_title'   => $s['title'],
				'post_name'    => $s['slug'],
				'post_excerpt' => $s['excerpt'],
				'post_content' => $s['body'],
				'menu_order'   => $i + 1,
			) );
			update_post_meta( $id, '_gg_tags', $s['tags'] );
		}
		$log[] = 'Created 8 services.';
	} else {
		$log[] = 'Services already exist; left unchanged.';
	}

	// 2. Team.
	$existing = get_posts( array( 'post_type' => 'gg_team', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
	if ( ! $existing ) {
		foreach ( ggs_team_seed() as $i => $m ) {
			$id = wp_insert_post( array(
				'post_type'    => 'gg_team',
				'post_status'  => 'publish',
				'post_title'   => $m['title'],
				'post_content' => $m['bio'],
				'menu_order'   => $i + 1,
			) );
			foreach ( array( 'designation', 'qualifications', 'email' ) as $k ) {
				update_post_meta( $id, '_gg_' . $k, $m[ $k ] );
			}
			update_post_meta( $id, '_gg_featured', '1' );
		}
		$log[] = 'Created 3 partner profiles. Add other staff under Team.';
	} else {
		$log[] = 'Team profiles already exist; left unchanged.';
	}

	// 3. Due dates.
	$existing = get_posts( array( 'post_type' => 'gg_duedate', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
	if ( ! $existing ) {
		foreach ( ggs_duedates_seed() as $i => $d ) {
			$id = wp_insert_post( array(
				'post_type'   => 'gg_duedate',
				'post_status' => 'publish',
				'post_title'  => $d[0],
				'menu_order'  => $i + 1,
			) );
			$keys = array( 'due', 'sort_day', 'frequency', 'law', 'note', 'source_url' );
			foreach ( $keys as $j => $k ) {
				update_post_meta( $id, '_gg_' . $k, (string) $d[ $j + 1 ] );
			}
		}
		$log[] = 'Created 10 due dates. Check each against the official portal and fill in "Verified on" (Due dates in the dashboard).';
	} else {
		$log[] = 'Due dates already exist; left unchanged.';
	}

	// 4. Categories.
	foreach ( array( 'Articles', 'Professional Updates', 'Notifications & Circulars' ) as $cat ) {
		if ( ! term_exists( $cat, 'category' ) ) {
			wp_insert_term( $cat, 'category' );
		}
	}

	// 5. Pages.
	$ids     = array();
	$created = 0;
	$updated = 0;
	foreach ( ggs_page_defs() as $slug => $def ) {
		list( $title, $content, $template ) = $def;
		$title = html_entity_decode( $title, ENT_QUOTES, 'UTF-8' );
		$page  = get_page_by_path( $slug );
		if ( $page ) {
			if ( $replace ) {
				if ( '' === get_post_meta( $page->ID, '_gg_previous_content', true ) ) {
					update_post_meta( $page->ID, '_gg_previous_content', wp_slash( $page->post_content ) );
					update_post_meta( $page->ID, '_gg_previous_template', get_post_meta( $page->ID, '_wp_page_template', true ) );
				}
				wp_update_post( array(
					'ID'           => $page->ID,
					'post_title'   => $title,
					'post_content' => $content,
					'post_status'  => 'publish',
				) );
				update_post_meta( $page->ID, '_wp_page_template', $template ? $template : 'default' );
				$updated++;
			}
			$ids[ $slug ] = $page->ID;
		} else {
			$ids[ $slug ] = wp_insert_post( array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
			) );
			if ( $template ) {
				update_post_meta( $ids[ $slug ], '_wp_page_template', $template );
			}
			$created++;
		}
	}
	$log[] = sprintf( 'Pages: %d created, %d updated.', $created, $updated );

	// 6. Front page and posts page.
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $ids['home'] );
	update_option( 'page_for_posts', $ids['resources'] );
	$log[] = 'Home set as front page; Resources & Publications set as the posts page.';

	// 6b. Firm details: office email, Kurukshetra office only, privacy page, remove WordPress sample page.
	$opts = get_option( 'ggs_settings', array() );
	if ( is_array( $opts ) ) {
		if ( isset( $opts['email'] ) && 'info@ggassociates.in' === $opts['email'] ) {
			$opts['email'] = 'mail@ggassociates.in';
		}
		$opts['branch'] = '';
		update_option( 'ggs_settings', $opts );
	}
	if ( false !== strpos( (string) get_theme_mod( 'gg_cta_text', '' ), 'Delhi' ) ) {
		remove_theme_mod( 'gg_cta_text' );
	}
	update_option( 'wp_page_for_privacy_policy', $ids['privacy-policy'] );
	$sample = get_page_by_path( 'sample-page' );
	if ( $sample && 'Sample Page' === $sample->post_title ) {
		wp_trash_post( $sample->ID );
	}
	$log[] = 'Office email set to mail@ggassociates.in; Delhi branch removed; Privacy Policy page linked; sample page moved to Trash.';

	// 7. Menus.
	ggs_build_menus( $ids );
	$log[] = 'Menus built: Main Menu, Utilities Toolbar, Resources Bar, Extra Bar A and Extra Bar B.';

	// 8. Retire the older Knowledge Hub plugin (its pages are replaced by real pages).
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if ( is_plugin_active( 'gg-knowledge-hub/gg-knowledge-hub.php' ) ) {
		deactivate_plugins( 'gg-knowledge-hub/gg-knowledge-hub.php' );
		$log[] = 'Deactivated the older GG Knowledge Hub plugin (you can delete it).';
	}

	if ( '' === get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}
	flush_rewrite_rules();
	do_action( 'litespeed_purge_all' );
	update_option( 'ggs_setup_done', time() );
	$log[] = 'Cache cleared.';
	return $log;
}

function ggs_menu_reset( $name ) {
	$menu = wp_get_nav_menu_object( $name );
	if ( $menu ) {
		foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
			wp_delete_post( $item->ID, true );
		}
		return $menu->term_id;
	}
	return wp_create_nav_menu( $name );
}

function ggs_menu_add( $menu_id, $args, $parent = 0 ) {
	$args = wp_parse_args( $args, array( 'menu-item-status' => 'publish', 'menu-item-parent-id' => $parent ) );
	return wp_update_nav_menu_item( $menu_id, 0, $args );
}

function ggs_menu_page( $menu_id, $page_id, $title, $parent = 0 ) {
	return ggs_menu_add( $menu_id, array(
		'menu-item-title'     => $title,
		'menu-item-object'    => 'page',
		'menu-item-object-id' => $page_id,
		'menu-item-type'      => 'post_type',
	), $parent );
}

function ggs_build_menus( $ids ) {
	$main = ggs_menu_reset( 'Main Menu' );
	ggs_menu_add( $main, array( 'menu-item-title' => 'Home', 'menu-item-url' => home_url( '/' ), 'menu-item-type' => 'custom', 'menu-item-description' => 'Welcome' ) );
	$about = ggs_menu_add( $main, array( 'menu-item-title' => 'About Us', 'menu-item-object' => 'page', 'menu-item-object-id' => $ids['about'], 'menu-item-type' => 'post_type', 'menu-item-description' => 'Our firm' ) );
	ggs_menu_page( $main, $ids['about'], 'Our Story', $about );
	ggs_menu_page( $main, $ids['our-group'], 'Our Group', $about );
	ggs_menu_page( $main, $ids['careers'], 'Careers & Articleship', $about );
	$svc = ggs_menu_add( $main, array( 'menu-item-title' => 'Services', 'menu-item-object' => 'page', 'menu-item-object-id' => $ids['services'], 'menu-item-type' => 'post_type', 'menu-item-description' => 'What we do' ) );
	foreach ( ggs_services() as $s ) {
		ggs_menu_add( $main, array(
			'menu-item-title'     => get_the_title( $s ),
			'menu-item-object'    => 'gg_service',
			'menu-item-object-id' => $s->ID,
			'menu-item-type'      => 'post_type',
		), $svc );
	}
	ggs_menu_add( $main, array( 'menu-item-title' => 'Our Team', 'menu-item-object' => 'page', 'menu-item-object-id' => $ids['team'], 'menu-item-type' => 'post_type', 'menu-item-description' => 'Partners' ) );
	$res = ggs_menu_add( $main, array( 'menu-item-title' => 'Resources', 'menu-item-object' => 'page', 'menu-item-object-id' => $ids['resources'], 'menu-item-type' => 'post_type', 'menu-item-description' => 'Publications' ) );
	foreach ( array( 'Articles', 'Professional Updates', 'Notifications & Circulars' ) as $cat ) {
		$term = get_term_by( 'name', $cat, 'category' );
		if ( $term ) {
			ggs_menu_add( $main, array(
				'menu-item-title'     => $cat,
				'menu-item-object'    => 'category',
				'menu-item-object-id' => $term->term_id,
				'menu-item-type'      => 'taxonomy',
			), $res );
		}
	}
	ggs_menu_add( $main, array( 'menu-item-title' => 'Contact', 'menu-item-object' => 'page', 'menu-item-object-id' => $ids['contact'], 'menu-item-type' => 'post_type', 'menu-item-description' => 'Get in touch' ) );

	$util = ggs_menu_reset( 'Utilities Toolbar' );
	$tool = function ( $slug, $title, $icon, $parent = 0 ) use ( $util, $ids ) {
		return ggs_menu_add( $util, array( 'menu-item-title' => $title, 'menu-item-object' => 'page', 'menu-item-object-id' => $ids[ $slug ], 'menu-item-type' => 'post_type', 'menu-item-classes' => $icon ), $parent );
	};
	$tool( 'calculators', 'Calculators', 'gg-i-calc' );
	$tool( 'due-dates', 'Due Date Calendar', 'gg-i-cal' );
	$acts = $tool( 'acts-rules', 'Acts, Rules & Notifications', 'gg-i-book' );
	foreach ( array( 'income-tax' => 'Income Tax', 'gst' => 'GST', 'mca-roc' => 'MCA & ROC', 'finance' => 'Finance', 'labour-law' => 'Labour Law', 'others' => 'Other Laws' ) as $slug => $t ) {
		$tool( $slug, $t, '', $acts );
	}
	$tool( 'professional-resources', 'Professional Resources', 'gg-i-link' );

	// Resources bar: five heads, each opening Act & Rules / Forms / Calculators / Others.
	$res_bar = ggs_menu_reset( 'Resources Bar' );
	foreach ( array( 'income-tax' => 'Income Tax', 'gst' => 'GST', 'mca-roc' => 'MCA / ROC', 'finance' => 'Finance', 'others' => 'Others' ) as $slug => $t ) {
		$head = ggs_menu_add( $res_bar, array( 'menu-item-title' => $t, 'menu-item-object' => 'page', 'menu-item-object-id' => $ids[ $slug ], 'menu-item-type' => 'post_type' ) );
		foreach ( array( 'acts' => 'Act & Rules', 'forms' => 'Forms', 'calculators' => 'Calculators', 'others' => 'Others' ) as $anchor => $label ) {
			ggs_menu_add( $res_bar, array( 'menu-item-title' => $label, 'menu-item-url' => get_permalink( $ids[ $slug ] ) . '#' . $anchor, 'menu-item-type' => 'custom' ), $head );
		}
		if ( 'gst' === $slug && ! empty( $ids['gst-registration-drafter'] ) ) {
			ggs_menu_add( $res_bar, array( 'menu-item-title' => 'GST Registration Drafter', 'menu-item-object' => 'page', 'menu-item-object-id' => $ids['gst-registration-drafter'], 'menu-item-type' => 'post_type' ), $head );
		}
	}
	ggs_menu_reset( 'Extra Bar A' );
	ggs_menu_reset( 'Extra Bar B' );

	$locations              = get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary']   = $main;
	$locations['utilities'] = $util;
	$locations['resources'] = $res_bar;
	set_theme_mod( 'nav_menu_locations', $locations );
}
