# Land Transfer Clearance Workflow

The workflow follows the administrative clearance process used by the DAR Negros Oriental Provincial Office.

1. **Application encoded and reviewed for completeness**  
   Legal Clearance Staff encode the application, link the subject parcel, and review the documentary requirements.

2. **Returned for compliance when incomplete or blocked**  
   Missing, deficient, or other blocking issues are handled through Request Compliance. This is an open corrective state, not a final decision, and the application resumes its saved stage after resolution.

3. **Payment order and Official Receipt recording**  
   Once documentary intake is complete, the payment order is prepared. Payment is handled through the appropriate cashier process, and the Official Receipt is recorded in DAR-LTCMS.

4. **With LTID for verification**  
   Legal Clearance Staff record that the complete application has been forwarded to LTID. LTID performs its verification outside the DAR-LTCMS user workspace.

5. **Returned to Legal Division**  
   Legal Clearance Staff record the return of the LTID verification results and LTC Form No. 4.

6. **Legal evaluation and CSW preparation**  
   Legal evaluation and Completed Staff Work (CSW) and the required supporting administrative documentation are prepared.

7. **With Chief Legal for review**  
   Legal Clearance Staff record the forwarding and later completion/return of the Chief Legal review. Chief Legal does not need a DAR-LTCMS account.

8. **With PARPO II for decision**  
   Legal Clearance Staff record the forwarding of the reviewed folder to PARPO II.

9. **PARPO II Approved decision recorded by Legal**  
   The application first reaches **PARPO II Decision Ready to Record**. After official PARPO II approval is received, Legal Clearance Staff record the decision authority, officer/signatory, official decision date, and the final **Approved** result. Approved is the only current final application decision in DAR-LTCMS. Issues that prevent approval remain open through the compliance workflow rather than being recorded as a new negative final decision.

10. **Clearance output preparation and release tracking**  
    The signed clearance result can be marked ready for release and later recorded as released to the client.

!!! note
    Release tracking is separate from the final application decision. An application remains **Approved** while its release status records whether the signed document is pending return to Legal, ready for release, or released to the client. Historical Not Approved / Denied records remain read-only compatibility records.

!!! note "System operator versus administrative authority"
    The authenticated Legal Clearance Staff user is the **recorder** of the workflow event. A tracked office such as LTID, Chief Legal, or PARPO II remains the real-world administrative authority and is not represented as the logged-in system actor.

!!! important
    No workflow step automatically changes parcel ownership or registry records.

The compliance loop is available from supported open stages, not only the initial completeness review. **Other** accepts a custom issue category; repeated notices retain their own history and resume stage.
