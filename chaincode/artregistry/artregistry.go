package main

// ArtRegistry — registro immutabile di proprietà/provenienza delle opere d'arte.
// On-chain SOLO dati non personali: codice registro, id opera, codice proprietario
// (pseudonimo, es. ZKM-XXXX), impronta SHA-256 del record. Nessun dato personale.
//
// Funzioni: Register, Transfer, Get, Verify, History.
// Lo storico immutabile dei passaggi di proprietà è dato da GetHistoryForKey.

import (
	"encoding/json"
	"fmt"
	"time"

	"github.com/hyperledger/fabric-contract-api-go/contractapi"
)

type ArtRegistry struct {
	contractapi.Contract
}

type ArtRecord struct {
	RegistryCode string `json:"registryCode"`
	WorkID       string `json:"workId"`
	OwnerCode    string `json:"ownerCode"`
	RecordHash   string `json:"recordHash"`
	UpdatedAt    string `json:"updatedAt"`
	Version      int    `json:"version"`
}

func artKey(registryCode string) string { return "art_" + registryCode }

func (a *ArtRegistry) read(ctx contractapi.TransactionContextInterface, registryCode string) (*ArtRecord, error) {
	b, err := ctx.GetStub().GetState(artKey(registryCode))
	if err != nil {
		return nil, err
	}
	if b == nil {
		return nil, nil
	}
	var r ArtRecord
	if err := json.Unmarshal(b, &r); err != nil {
		return nil, err
	}
	return &r, nil
}

// Register — prima registrazione di un'opera. Errore se già presente (creazione una tantum).
func (a *ArtRegistry) Register(ctx contractapi.TransactionContextInterface, registryCode, workID, ownerCode, recordHash, updatedAt string) error {
	if registryCode == "" || recordHash == "" {
		return fmt.Errorf("registryCode e recordHash sono obbligatori")
	}
	existing, err := a.read(ctx, registryCode)
	if err != nil {
		return err
	}
	if existing != nil {
		return fmt.Errorf("opera %s gia registrata", registryCode)
	}
	rec := ArtRecord{RegistryCode: registryCode, WorkID: workID, OwnerCode: ownerCode, RecordHash: recordHash, UpdatedAt: updatedAt, Version: 1}
	b, _ := json.Marshal(rec)
	if err := ctx.GetStub().PutState(artKey(registryCode), b); err != nil {
		return err
	}
	ev, _ := json.Marshal(map[string]string{"type": "register", "registryCode": registryCode, "ownerCode": ownerCode, "recordHash": recordHash})
	return ctx.GetStub().SetEvent("Register", ev)
}

// Transfer — passaggio di proprietà: aggiorna proprietario e impronta; la versione
// precedente resta nello storico immutabile (GetHistoryForKey).
func (a *ArtRegistry) Transfer(ctx contractapi.TransactionContextInterface, registryCode, newOwnerCode, recordHash, updatedAt string) error {
	rec, err := a.read(ctx, registryCode)
	if err != nil {
		return err
	}
	if rec == nil {
		return fmt.Errorf("opera %s non registrata", registryCode)
	}
	rec.OwnerCode = newOwnerCode
	rec.RecordHash = recordHash
	rec.UpdatedAt = updatedAt
	rec.Version++
	b, _ := json.Marshal(rec)
	if err := ctx.GetStub().PutState(artKey(registryCode), b); err != nil {
		return err
	}
	ev, _ := json.Marshal(map[string]string{"type": "transfer", "registryCode": registryCode, "ownerCode": newOwnerCode, "recordHash": recordHash})
	return ctx.GetStub().SetEvent("Transfer", ev)
}

// Get — record corrente.
func (a *ArtRegistry) Get(ctx contractapi.TransactionContextInterface, registryCode string) (*ArtRecord, error) {
	rec, err := a.read(ctx, registryCode)
	if err != nil {
		return nil, err
	}
	if rec == nil {
		return nil, fmt.Errorf("opera %s non trovata", registryCode)
	}
	return rec, nil
}

// Verify — true se l'impronta fornita coincide col record corrente.
func (a *ArtRegistry) Verify(ctx contractapi.TransactionContextInterface, registryCode, recordHash string) (bool, error) {
	rec, err := a.read(ctx, registryCode)
	if err != nil {
		return false, err
	}
	if rec == nil {
		return false, nil
	}
	return rec.RecordHash == recordHash, nil
}

type HistoryEntry struct {
	TxID      string     `json:"txId"`
	Timestamp string     `json:"timestamp"`
	IsDelete  bool       `json:"isDelete"`
	Value     *ArtRecord `json:"value,omitempty"`
}

// History — storico immutabile delle modifiche della chiave = provenienza completa.
func (a *ArtRegistry) History(ctx contractapi.TransactionContextInterface, registryCode string) ([]HistoryEntry, error) {
	it, err := ctx.GetStub().GetHistoryForKey(artKey(registryCode))
	if err != nil {
		return nil, err
	}
	defer it.Close()
	var out []HistoryEntry
	for it.HasNext() {
		m, err := it.Next()
		if err != nil {
			return nil, err
		}
		e := HistoryEntry{TxID: m.TxId, IsDelete: m.IsDelete}
		if m.Timestamp != nil {
			e.Timestamp = time.Unix(m.Timestamp.Seconds, int64(m.Timestamp.Nanos)).UTC().Format("2006-01-02T15:04:05Z")
		}
		if !m.IsDelete && m.Value != nil {
			var r ArtRecord
			if json.Unmarshal(m.Value, &r) == nil {
				e.Value = &r
			}
		}
		out = append(out, e)
	}
	return out, nil
}

func main() {
	cc, err := contractapi.NewChaincode(&ArtRegistry{})
	if err != nil {
		fmt.Printf("Errore creazione chaincode artregistry: %v\n", err)
		return
	}
	if err := cc.Start(); err != nil {
		fmt.Printf("Errore avvio chaincode artregistry: %v\n", err)
	}
}
