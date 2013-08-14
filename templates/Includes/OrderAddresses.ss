<table class="table table-bordered">
	<tr>
		<th><% _t('OrderAddresses.BILLING_ADDRESS','Billing Address') %></th>
		<th><% _t('OrderAddresses.SHIPPING_ADDRESS','Shipping Address') %></th>
	</tr>
	<tr>
		<td>
			$BillingFirstName $BillingSurname 
				
			<% if MemberEmail %>
				<a href="Mailto:$MemberEmail">$MemberEmail</a>
			<% end_if %>
			<br />
				
			<% if BillingCompany %>      $BillingCompany<br />      <% end_if %>
			<% if BillingAddress %>      $BillingAddress<br />      <% end_if %>
			<% if BillingAddressLine2 %> $BillingAddressLine2<br /> <% end_if %>
			<% if BillingCity %>         $BillingCity<br />         <% end_if %>
			<% if BillingPostalCode %>   $BillingPostalCode<br />   <% end_if %>
			<% if BillingState %>        $BillingState<br />        <% end_if %>
			<% if BillingRegionName %>   $BillingRegionName<br />  <% end_if %>
			<% if BillingCountryName %>  $BillingCountryName<br />  <% end_if %>
		</td>
		
		<td>
			$ShippingFirstName $ShippingSurname <br />
			
			<% if ShippingCompany %>      $ShippingCompany<br />      <% end_if %>
			<% if ShippingAddress %>      $ShippingAddress<br />      <% end_if %>
			<% if ShippingAddressLine2 %> $ShippingAddressLine2<br /> <% end_if %>
			<% if ShippingCity %>         $ShippingCity<br />         <% end_if %>
			<% if ShippingPostalCode %>   $ShippingPostalCode<br />   <% end_if %>
			<% if ShippingState %>        $ShippingState<br />        <% end_if %>
			<% if ShippingRegionName %>   $ShippingRegionName<br />  <% end_if %>
			<% if ShippingCountryName %>  $ShippingCountryName<br />  <% end_if %>
		</td>
	</tr>
</table>
