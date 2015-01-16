<?php



/**
 * adds functionality to Products
 *
 *
 *
 */
class ProductQuestion_ProductDecorator extends DataExtension {

	private static $db = array('ConfigureLabel' => 'Varchar(50)');

	private static $belongs_many_many = array('ProductQuestions' => 'ProductQuestion');

	function updateCMSFields(FieldList $fields) {
		$productQuestions = ProductQuestion::get();
		if($productQuestions->count()){
			$fields->addFieldToTab("Root.Questions", new TextField("ConfigureLabel", _t("ProductQuestion.CONFIGURE_LINK_LABEL", "Configure link label")));
			$fields->addFieldToTab("Root.Questions",
				$gridField = new CheckboxSetField(
					'ProductQuestions',
					_t("ProductQuestion.PLURAL_NAME", "Product Questions"),
					ProductQuestion::get()->map("ID", "Title")->toArray()
				)
			);
		}
		$fields->addFieldToTab("Root.Questions", new LiteralField("EditProductQuestions", "<h2><a href=\"/admin/product-config/ProductQuestion/\">"._t("ProductQuestion.EDIT_PRODUCT_QUESTIONS", "Edit Product Questions")."</a></h2>"));
	}

	function ProductQuestionsAnswerFormLink($id = 0){
		return $this->owner->Link("productquestionsanswerselect")."/".$id."/?BackURL=".urlencode(Controller::curr()->Link());
	}

	/**
	 * returns a label that is used to allow customers to open the form
	 * for answering the Product Questions.
	 * @return String
	 */
	public function CustomConfigureLabel(){
		if($this->HasProductQuestions()) {
			if($this->owner->ConfigureLabel) {
				return $this->owner->ConfigureLabel;
			}
			else {
				return _t("ProductQuestion.CONFIGURE", "Configure");
			}
		}
	}

	/**
	 * Does this buyable have product questions?
	 * @return Boolean
	 */
	public function HasProductQuestions(){
		if($this->owner->ProductQuestions()) {
			if($this->owner->ProductQuestions()->count()) {
				return true;
			}
		}
		return true;
	}

}



/**
 * adds functionality to ProductControllers
 *
 *
 *
 */
class ProductQuestion_ProductControllerDecorator extends Extension {


	/**
	 * we need this here to
	 * because otherwise the extension will not work
	 */
	private static $allowed_actions = array(
		"ProductQuestionsAnswerForm",
		"productquestionsanswerselect"
	);

	/**
	 * Stores the related OrderItem
	 * @var OrderItem
	 */
	protected $productQuestionOrderItem = null;

	/**
	 * renders a form with the product questions
	 * @return String (HTML)
	 */
	function productquestionsanswerselect(){
		$this->getProductQuestionOrderItem();
		return $this->owner->customise(
			array(
				"Title" => $this->productQuestionOrderItem->getTableTitle(),
				"Form" => $this->ProductQuestionsAnswerForm()
			)
		)->renderWith("productquestionsanswerselect") ;
	}

	/**
	 * returns a form with questions
	 * @return Form
	 */
	function ProductQuestionsAnswerForm(){
		$this->getProductQuestionOrderItem();
		if($this->productQuestionOrderItem) {
			return $this->productQuestionOrderItem->ProductQuestionsAnswerForm($this->owner, $name = "ProductQuestionsAnswerForm");
		}
	}

	/**
	 * returns the fields from the form
	 * @return FieldSet
	 */
	function ProductQuestionsAnswerFormFields(){
		$fieldSet = new FieldList();
		$product = $this->owner->dataRecord;
		$productQuestions = $product->ProductQuestions();
		if($productQuestions) {
			foreach($productQuestions as $productQuestion) {
				$fieldSet->push($productQuestion->getFieldForProduct($product));
			}
		}
		return $fieldSet;
	}

	/**
	 * processes a form and
	 * adds product question answer(s) to order item.
	 * The answers are added as HTML and JSON
	 * and redirects back to the previous page or a set BackURL
	 * (set in the form data)
	 * @param Array $data - form data
	 * @param Form form - data from the form
	 */
	function addproductquestionsanswer($data, $form){
		$this->getProductQuestionOrderItem();
		$data = Convert::raw2sql($data);
		if($this->productQuestionOrderItem) {
			$this->productQuestionOrderItem->updateOrderItemWithProductAnswers(
				$answers = $data["ProductQuestions"],
				$write = true
			);
		}
		if(isset($data["BackURL"])){
			$this->owner->redirect($data["BackURL"]);
		}
		else {
			$this->owner->redirectBack();
		}
	}

	/**
	 * retrieves order item from post / get variables.
	 * @return OrderItem | Null
	 */
	protected function getProductQuestionOrderItem(){
		$id = intval($this->owner->request->param("ID"));
		if(!$id) {
			$id = intval($this->owner->request->postVar("OrderItemID"));
		}
		if(!$id) {
			$id = intval($this->owner->request->getVar("OrderItemID"));
		}
		if($id) {
			$this->productQuestionOrderItem = OrderItem::get()->byID($id);
		}
		if(!$this->productQuestionOrderItem) {
			user_error("NO this->productQuestionOrderItem specified");
		}
		return $this->productQuestionOrderItem;
	}

}
