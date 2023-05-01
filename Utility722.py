from typing import Dict, List, Optional, Tuple
from dateutil import parser
import datetime


class UtilityBillType(str):
    Water = "water"
    Elec = "electricity"


class Utility722:
    Resident = Tuple[
        str, int, Optional[int], Optional[float]
    ]  # name, start_date, end_date, paid
    Bill = Tuple[int, int, float]

    def __init__(self) -> None:
        self.residents: Dict[str, Utility722.Resident] = {}
        self.water_bills: List[Utility722.Bill] = []
        self.elec_bills: List[Utility722.Bill] = []
        self.daily_water_bill_per_person: Dict[datetime.date, float] = {}
        self.daily_elec_bill_per_person: Dict[datetime.date, float] = {}

        resident_file_name = "./resident.txt"
        elec_bill_file_name = "./elec_bill.txt"
        water_bill_file_name = "./water_bill.txt"

        for line in open(resident_file_name):
            row_array = line.strip().split()
            print(row_array)
            end_date = parser.parse(row_array[2]) if len(row_array) == 3 else None
            self.residents[row_array[0]] = (
                row_array[0],
                parser.parse(row_array[1]),
                end_date,
                None,
            )

        for line in open(elec_bill_file_name):
            row_array = line.strip().split()
            print(float(row_array[2]))
            self.elec_bills.append(
                (
                    parser.parse(row_array[0]),
                    parser.parse(row_array[1]),
                    float(row_array[2]),
                )
            )

        for line in open(water_bill_file_name):
            row_array = line.strip().split()
            self.water_bills.append(
                (
                    parser.parse(row_array[0]),
                    parser.parse(row_array[1]),
                    float(row_array[2]),
                )
            )

        self._validate_bill_dates(self.water_bills)
        self._validate_bill_dates(self.elec_bills)
        self._calculate_bill_per_person_per_day(UtilityBillType.Elec)
        self._calculate_bill_per_person_per_day(UtilityBillType.Water)

    def _parse_date(self, date_string: str) -> int:
        date_parts = date_string.split("-")
        return int(f"{date_parts[0]}{date_parts[1]}{date_parts[2]}")

    def _validate_bill_dates(self, bills) -> None:
        for i in range(1, len(bills)):
            if (bills[i][0] - bills[i - 1][1]).days != 1:
                print(bills[i][0])
                raise ValueError("wrong begin_date")
            if bills[i][1] <= bills[i][0]:
                raise ValueError("ending date should be greater than begin date")

    def _calculate_bill_per_person_per_day(self, bill_type: UtilityBillType) -> None:
        bills = (
            self.elec_bills if bill_type == UtilityBillType.Elec else self.water_bills
        )
        daily_bill_per_person = (
            self.daily_elec_bill_per_person
            if bill_type == UtilityBillType.Elec
            else self.daily_water_bill_per_person
        )
        for bill in bills:
            price = bill[2] / ((bill[1] - bill[0]).days + 1)
            d = bill[0]
            while d <= bill[1]:
                daily_bill_per_person[d] = price / self._get_num_of_residents(d)
                d += datetime.timedelta(days=1)

    def _get_num_of_residents(self, d: datetime) -> int:
        res = 0
        for resident in self.residents.values():
            if resident[1] <= d and (resident[2] is None or d <= resident[2]):
                res += 1
        return res

    def get_total_bill_for_resident(
        self, name: str, bill_type: UtilityBillType
    ) -> float:
        resident = self.residents[name]
        return self._get_bill_for_resident_helper(
            resident[1], resident[2], bill_type
        )

    def _get_bill_for_resident_helper(
        self, begin: datetime, end: datetime, bill_type: UtilityBillType
    ) -> float:
        res = 0.0
        d = begin
        if end is None:
            end = datetime.datetime.now()
        while d <= end:
            if bill_type == UtilityBillType.Elec:
                if d in self.daily_elec_bill_per_person:
                    res += self.daily_elec_bill_per_person[d]
            if bill_type == UtilityBillType.Water:
                if d in self.daily_water_bill_per_person:
                    res += self.daily_water_bill_per_person[d]
            d += datetime.timedelta(days=1)
        return res
