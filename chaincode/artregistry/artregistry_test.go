package main

import (
	"testing"

	"github.com/hyperledger/fabric-chaincode-go/shimtest"
	"github.com/hyperledger/fabric-contract-api-go/contractapi"
)

func ctxWith(stub *shimtest.MockStub) *contractapi.TransactionContext {
	ctx := new(contractapi.TransactionContext)
	ctx.SetStub(stub)
	return ctx
}

func TestRegisterGetVerifyTransfer(t *testing.T) {
	a := new(ArtRegistry)
	stub := shimtest.NewMockStub("artregistry", nil)
	ctx := ctxWith(stub)

	// Register
	stub.MockTransactionStart("t1")
	if err := a.Register(ctx, "REG1", "592", "ZKM-A", "hash1", "2026-01-01"); err != nil {
		t.Fatalf("Register: %v", err)
	}
	stub.MockTransactionEnd("t1")

	// Register duplicato -> errore
	stub.MockTransactionStart("t2")
	if err := a.Register(ctx, "REG1", "592", "ZKM-A", "hash1", "2026-01-01"); err == nil {
		t.Fatal("Register duplicato avrebbe dovuto fallire")
	}
	stub.MockTransactionEnd("t2")

	// Get
	rec, err := a.Get(ctx, "REG1")
	if err != nil || rec.OwnerCode != "ZKM-A" || rec.Version != 1 || rec.RecordHash != "hash1" {
		t.Fatalf("Get inatteso: %+v err=%v", rec, err)
	}

	// Verify
	if ok, _ := a.Verify(ctx, "REG1", "hash1"); !ok {
		t.Fatal("Verify(hash1) dovrebbe essere true")
	}
	if bad, _ := a.Verify(ctx, "REG1", "hash-sbagliato"); bad {
		t.Fatal("Verify(hash sbagliato) dovrebbe essere false")
	}

	// Transfer
	stub.MockTransactionStart("t3")
	if err := a.Transfer(ctx, "REG1", "ZKM-B", "hash2", "2026-02-02"); err != nil {
		t.Fatalf("Transfer: %v", err)
	}
	stub.MockTransactionEnd("t3")

	rec2, _ := a.Get(ctx, "REG1")
	if rec2.OwnerCode != "ZKM-B" || rec2.Version != 2 || rec2.RecordHash != "hash2" {
		t.Fatalf("Dopo Transfer inatteso: %+v", rec2)
	}
	if ok, _ := a.Verify(ctx, "REG1", "hash2"); !ok {
		t.Fatal("Verify(hash2) dovrebbe essere true dopo il transfer")
	}
}

func TestTransferSenzaRegister(t *testing.T) {
	a := new(ArtRegistry)
	stub := shimtest.NewMockStub("artregistry", nil)
	ctx := ctxWith(stub)
	stub.MockTransactionStart("x")
	if err := a.Transfer(ctx, "NOPE", "ZKM-Z", "h", "2026"); err == nil {
		t.Fatal("Transfer su opera non registrata avrebbe dovuto fallire")
	}
	stub.MockTransactionEnd("x")
}
